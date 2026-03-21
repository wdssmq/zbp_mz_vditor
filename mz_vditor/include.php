<?php
RegisterPlugin("mz_vditor", "ActivePlugin_mz_vditor");

function ActivePlugin_mz_vditor()
{
  // 接口挂载 - 编辑页头部
  Add_Filter_Plugin('Filter_Plugin_Edit_Begin', 'mz_vditor_Edit_Begin');
  // 接口挂载 - 编辑页底部
  Add_Filter_Plugin('Filter_Plugin_Edit_End', 'mz_vditor_Edit_End');
}

// 接口函数 - 编辑页头部
function mz_vditor_Edit_Begin()
{
  echo '<link rel="stylesheet" href="' . mz_vditor_Path('lib-css', 'host') . '">';
  echo '<link rel="stylesheet" href="' . mz_vditor_Path('plugin-css', 'host') . '">';
}

// 接口函数 - 编辑页底部
function mz_vditor_Edit_End()
{
  $postInfo = mz_vditor_GetPost();
  echo sprintf(
    '<script>window.mzVditorPostInfo = %s;</script>',
    json_encode($postInfo, JSON_UNESCAPED_UNICODE)
  );
  $uploadInfo = mz_vditor_GetUpload();
  echo sprintf(
    '<script>window.mzVditorUploadInfo = %s;</script>',
    json_encode($uploadInfo, JSON_UNESCAPED_UNICODE)
  );
  echo '<script>window.mzVditorCDN = "' . mz_vditor_Path('lib', 'host') . '";</script>';
  echo '<script src="' . mz_vditor_Path('lib-js', 'host') . '"></script>';
  echo '<script src="' . mz_vditor_Path('plugin-js', 'host') . '"></script>';
}

// 工具函数 - 获取文章信息
function mz_vditor_GetPost()
{
  global $article;
  if (!$article) {
    return null;
  }
  $contentMarkdown = '';
  $introMarkdown   = '';
  // 已经存在的文章
  if ($article->ID > 0) {
    // 是否存在 markdown 元信息
    if (null !== $article->Metas->md_content) {
      $contentMarkdown = $article->Metas->md_content;
    }
    if (null !== $article->Metas->md_intro) {
      $introMarkdown = $article->Metas->md_intro;
    }
  }

  return [
    'ContentMarkdown' => $contentMarkdown,
    'IntroMarkdown' => $introMarkdown,
  ];
}

// 工具函数 - 获取文件上传地址及类型
function mz_vditor_GetUpload()
{
  global $zbp;
  $uploadUrl = $zbp->host . 'zb_users/plugin/mz_vditor/upload.php';
  $fileTypes = str_replace('|', ', .', $zbp->option['ZC_UPLOAD_FILETYPE']);
  return [
    'url' => $uploadUrl,
    'accept' => $fileTypes,
    'token' => $zbp->GetCSRFToken('mz_vditor')
  ];
}

// 工具函数 - 获取插件路径
function mz_vditor_Path($file, $t = 'path')
{
  global $zbp;
  $result = $zbp->$t . 'zb_users/plugin/mz_vditor/';
  switch ($file) {
    case 'plugin-js':
      return $result . 'lib/vditor.editor.js';
      break;
    case 'plugin-css':
      return $result . 'lib/vditor.editor.css';
      break;
    case 'lib-js':
      return $result . 'lib/dist/index.min.js';
      break;
    case 'lib-css':
      return $result . 'lib/dist/index.css';
      break;
    case 'lib':
      return $result . 'lib';
      break;
    case 'main':
      return $result . 'main.php';
      break;
    default:
      return $result . $file;
  }
}

function InstallPlugin_mz_vditor() {}

function UninstallPlugin_mz_vditor() {}
