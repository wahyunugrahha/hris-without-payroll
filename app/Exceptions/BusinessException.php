<?php

namespace App\Exceptions;

/**
 * Pelanggaran aturan bisnis yang pesannya aman & memang ditujukan untuk user.
 * Exception lain dianggap error teknis: dicatat ke log dan tidak ditampilkan mentah.
 */
class BusinessException extends \RuntimeException {}
