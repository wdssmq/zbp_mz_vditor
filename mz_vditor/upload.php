<?php
require '../../../zb_system/function/c_system_base.php';
require 'upload.class.php';

$zbp->Load();
header('Content-Type: application/json; charset=utf-8');

class mz_VditorUploadService extends mz_FileUploadParser
{
  /**
   * @var ZBlogPHP
   */
  private $zbp;

  public function __construct($zbp)
  {
    $this->zbp = $zbp;
  }

  /**
   * 处理上传并返回响应对象
   *
   * @return object
   */
  public function run()
  {
    $resData = (object)[
      'msg' => '',
      'code' => 0,
      'data' => (object)[
        'errFiles' => [],
        'succMap' => (object)[]
      ]
    ];

    if (!$this->zbp->CheckRights('UploadPst')) {
      $resData->msg = '没有上传权限';
      $resData->code = 1;
      return $resData;
    }

    if (!$this->zbp->CheckPlugin('mz_vditor')) {
      $resData->msg = '插件未启用';
      $resData->code = 1;
      return $resData;
    }

    $token = GetVars('HTTP_X_UPLOAD_TOKEN', 'SERVER');
    if (!$this->zbp->VerifyCSRFToken($token, 'mz_vditor')) {
      $resData->msg = 'CSRF Token 验证失败';
      $resData->code = 1;
      return $resData;
    }

    $errFiles = [];
    $uploads = $this->upload($errFiles);

    foreach ($uploads as $upload) {
      $resData->data->succMap->{$upload->SourceName} = $upload->Url;
    }
    $resData->data->errFiles = $errFiles;
    if (count($uploads) === 0 && count($errFiles) > 0) {
      // $resData->msg = '上传失败';
      $resData->code = 1;
    }

    return $resData;
  }

  /**
   * 处理文件上传逻辑
   * 
   * @param array $errFiles 输出失败文件名数组
   * @return Upload[]
   */
  protected function upload(array &$errFiles = [])
  {
    $files = static::parse();
    $uploadList = [];
    $autoRename = GetVars('auto_rename', 'POST') == 'on' || GetVars('auto_rename', 'POST') == true;
    $errMsg = '';

    foreach ($files as $file) {
      if (!$file['is_valid'] || !is_uploaded_file($file['tmp_name'])) {
        $errMsg = $file['error_message'] ?? '未知上传错误';
        $errFiles[] = $file['name'] . ' (' . $errMsg . ')';
        continue;
      }

      $upload = new Upload();
      $upload->Name = $file['name'];
      if ($autoRename) {
        $tempArr = explode('.', $upload->Name);
        $fileExt = strtolower(trim(array_pop($tempArr)));
        $upload->Name = date('YmdHis') . time() . rand(10000, 99999) . '.' . $fileExt;
      }

      $upload->SourceName = $file['name'];
      $upload->MimeType = $file['type'];
      $upload->Size = $file['size'];
      $upload->AuthorID = $this->zbp->user->ID;

      if ($this->uploadNameExistsInMonth($upload->Name)) {
        $errFiles[] = $file['name'] . ' <b>(文件名已存在)</b>';
        continue;
      }

      if (!$upload->CheckExtName() || !$upload->CheckSize()) {
        $errFiles[] = $file['name'] . ' <b>(文件类型或大小不符合要求)</b>';
        continue;
      }

      if (!$upload->SaveFile($file['tmp_name'])) {
        $errFiles[] = $file['name'] . ' <b>(文件保存失败)</b>';
        continue;
      }

      $upload->Save();
      $this->zbp->AddCache($upload);
      $uploadList[] = $upload;
    }

    if (count($uploadList) > 0) {
      CountMemberArray(array($this->zbp->user->ID), array(0, 0, 0, count($uploadList)));
      foreach ($uploadList as $upload) {
        foreach ($GLOBALS['hooks']['Filter_Plugin_PostUpload_Succeed'] as $fpname => &$fpsignal) {
          $fpname($upload);
        }
      }
    }

    return $uploadList;
  }

  /**
   * 检查指定文件名在当前月份内是否已存在。
   *
   * @param string $name 文件名
   * @return bool
   */
  protected function uploadNameExistsInMonth($name)
  {
    // 获取当前月份的开始和结束时间戳
    $d1 = strtotime(date('Y-m-01', time()));
    $d2 = strtotime(date('Y-m-d', strtotime(date('Y-m-01', time()) . ' +1 month -1 day')));

    // 查询同名且上传时间在当前月份内的文件
    $w = array();
    $w[] = array('=', 'ul_Name', $name);
    $w[] = array('>=', 'ul_PostTime', $d1);
    $w[] = array('<=', 'ul_PostTime', $d2);
    $exists = $this->zbp->GetUploadList('*', $w);

    // 返回查询结果
    return count($exists) > 0;
  }
}

$service = new mz_VditorUploadService($zbp);
$resData = $service->run();
echo json_encode($resData, JSON_UNESCAPED_UNICODE);
exit();
