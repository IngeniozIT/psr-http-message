<?php
declare(strict_types = 1);

namespace IngeniozIT\Http\Message\Enums;

/**
 * List of file statuses.
 */
class File
{
    /**
     * @var array[] List of file error statuses.
     */
    const ERROR_STATUS = [
        \UPLOAD_ERR_OK => true, // 0
        \UPLOAD_ERR_INI_SIZE => true, // 1
        \UPLOAD_ERR_FORM_SIZE => true, // 2
        \UPLOAD_ERR_PARTIAL => true, // 3
        \UPLOAD_ERR_NO_FILE => true, // 4
        \UPLOAD_ERR_NO_TMP_DIR => true, // 6
        \UPLOAD_ERR_CANT_WRITE => true, // 7
        \UPLOAD_ERR_EXTENSION => true, // 8
    ];
}
