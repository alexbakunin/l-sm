<?php

namespace App\Helpers;

class NumberHelper
{
    const DAYS_DECLINES = [
        'день',
        'дня',
        'дней'
    ];

    /**
     * Получаем удобочитаемый размер файла
     * @param int $filesize
     * @return string
     */
    public static function filesizeFormat(int $filesize): string
    {
        $formats = array(' байт', ' Кб', ' Мб', ' Гб', ' Тб');// варианты размера файла
        $format = 0;// формат размера по-умолчанию

        while ($filesize > 1024 && count($formats) != ++$format) {
            $filesize = round($filesize / 1024, 2);
        }
        $formats[] = ' Тб';

        return $filesize . $formats[$format];
    }

    /**
     * @param int $number
     * @param string|array $titles
     * @param bool $show_number
     * @return string
     */
    public static function numberTranslation(int $number, string|array $titles = self::DAYS_DECLINES, bool $show_number = true): string
    {
        if (is_string($titles)) {
            $titles = preg_split('/, */', $titles);
        }
        // когда указано 2 элемента
        if (empty($titles[2])) {
            $titles[2] = $titles[1];
        }
        $cases = [2, 0, 1, 1, 1, 2];
        $intnum = abs((int)strip_tags($number));
        $title_index = ($intnum % 100 > 4 && $intnum % 100 < 20)
            ? 2
            : $cases[min($intnum % 10, 5)];

        return ($show_number ? "$number " : '') . $titles[$title_index];
    }
}
