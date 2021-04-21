<?php
/**
 * Команда импорта пунктов выдачи заказа "Деловых Линий"
 */
namespace RAAS\CMS\DelLin;

use RAAS\Application;
use RAAS\Command;

/**
 * Команда импорта пунктов выдачи заказа "Деловых Линий"
 */
class PVZImportCommand extends Command
{
    /**
     * Выполнение команды
     * @param string $appkey Ключ приложения
     * @param string $filename Файл, в который будет импортироваться список
     *                         (относительно корня приложения)
     */
    public function process($appkey = null, $filename = 'dellin.pvz.json')
    {
        $filepath = Application::i()->baseDir . '/' . $filename;
        $api = new API($appkey);
        $response = $api->method('public/terminals', [], 3);
        $url = $response['url'];
        $text = file_get_contents($url);
        $tmpname = tempnam(sys_get_temp_dir(), '');
        file_put_contents($tmpname, $text);
        rename($tmpname, $filepath);
    }
}
