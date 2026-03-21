<?php

class mz_FileUploadParser
{
  public static function parse(?array $files = null): array
  {
    $files = $files ?? $_FILES;
    $result = [];

    foreach ($files as $field => $file) {
      if (!isset($file['name'], $file['type'], $file['tmp_name'], $file['error'], $file['size'])) {
        continue;
      }

      if (is_array($file['name'])) {
        $count = count($file['name']);
        for ($i = 0; $i < $count; $i++) {
          $error = (int)($file['error'][$i] ?? UPLOAD_ERR_NO_FILE);
          $result[] = [
            'field' => $field,
            'index' => $i,
            'name' => $file['name'][$i] ?? '',
            'type' => $file['type'][$i] ?? '',
            'tmp_name' => $file['tmp_name'][$i] ?? '',
            'error' => $error,
            'size' => (int)($file['size'][$i] ?? 0),
            'is_valid' => $error === UPLOAD_ERR_OK,
            'error_message' => self::errorMessage($error),
          ];
        }
      } else {
        $error = (int)$file['error'];
        $result[] = [
          'field' => $field,
          'index' => 0,
          'name' => $file['name'] ?? '',
          'type' => $file['type'] ?? '',
          'tmp_name' => $file['tmp_name'] ?? '',
          'error' => $error,
          'size' => (int)($file['size'] ?? 0),
          'is_valid' => $error === UPLOAD_ERR_OK,
          'error_message' => self::errorMessage($error),
        ];
      }
    }

    return $result;
  }

  public static function errorMessage(int $code): ?string
  {
    switch ($code) {
      case UPLOAD_ERR_OK:
        return null;
      case UPLOAD_ERR_INI_SIZE:
        return '文件超过 php.ini 的 upload_max_filesize 限制';
      case UPLOAD_ERR_FORM_SIZE:
        return '文件超过表单 MAX_FILE_SIZE 限制';
      case UPLOAD_ERR_PARTIAL:
        return '文件只上传了一部分';
      case UPLOAD_ERR_NO_FILE:
        return '没有文件上传';
      case UPLOAD_ERR_NO_TMP_DIR:
        return '缺少临时目录';
      case UPLOAD_ERR_CANT_WRITE:
        return '写入磁盘失败';
      case UPLOAD_ERR_EXTENSION:
        return '被 PHP 扩展中止上传';
      default:
        return '未知错误';
    }
  }
}
