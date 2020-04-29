<?php
/**
 * @package evas-php/evas-loader
 */
namespace Evas\Loader;

use Evas\Base\Helpers\RunDirHelper;

require_once dirname(dirname(__DIR__)) . '/evas-base/src/Helpers/RunDirHelper.php';

/**
 * Автозагрузчик.
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 1.0
 */
class Loader
{
    /**
     * @var string базовая директория загрузки
     */
    public $baseDir;

    /**
     * @var array директории
     */
    public $dirs = [];

    /**
     * @var array маппинг неймспейсов и путей
     */
    public $namespaces = [];

    /**
     * Конструктор.
     * @param string|null базовая директория загрузки
     */
    public function __construct(string $baseDir = null)
    {
        if (empty($baseDir)) {
            $baseDir = RunDirHelper::getDir();
        }
        $this->baseDir = RunDirHelper::addEndDirSlash($baseDir);
    }

    /**
     * Установка базовой директории.
     * @param string
     * @return self
     */
    public function baseDir(string $dir)
    {
        $this->baseDir = $dir;
        return $this;
    }

    /**
     * Установка директории загрузки.
     * @param string
     * @return self
     */
    public function dir(string $dir)
    {
        $this->dirs[] = $dir;
        return $this;
    }

    /**
     * Установка директорий загрузки.
     * @param array
     * @return self
     */
    public function dirs(array $dirs)
    {
        foreach ($dirs as &$dir) {
            $this->dir($dir);
        }
        return $this;
    }

    /**
     * Установка пути загрузки для пространства имен.
     * @param string namespace
     * @param string path
     * @return self
     */
    public function namespace(string $name, string $path)
    {
        $name = str_replace('\\', '\\\\', $name);
        $this->namespaces[$name] = $path;
        return $this;
    }

    /**
     * Установка нескольких путей загрузки для пространств имен.
     * @param array namespaces
     * @return self
     */
    public function namespaces(array $namespaces)
    {
        foreach ($namespaces as $name => $path) {
            $this->namespace($name, $path);
        }
        return $this;
    }

    /**
     * Добавление пространств имен компонентов evas-php.
     * @throws Exception
     * @return self
     */
    public function useEvas()
    {
        $loaderDir = str_replace('\\', '/', __DIR__);
        $evasDir = dirname(dirname($loaderDir));
        $evasPath = str_replace(str_replace('\\', '/', $this->baseDir), '', $evasDir);
        $dirs = scandir($evasDir);
        foreach ($dirs as &$dir) {
            $path = "$evasPath/$dir";
            if (preg_match('/^evas-/', $dir)) {
                $namespaceParts = explode('-', $dir);
                foreach ($namespaceParts as &$part) {
                    $part = ucfirst($part);
                }
                $namespace = implode('\\', $namespaceParts) . '\\';
                $this->namespace($namespace, "$path/src/");
            }
        }
        return $this;
    }


    /**
     * Запуск автозагрузки.
     * @return self
     */
    public function run()
    {
        spl_autoload_register([$this, 'autoload']);
        return $this;
    }

    /**
     * Остановка автозагрузки.
     * @return self
     */
    public function stop()
    {
        spl_autoload_unregister([$this, 'autoload']);
        return $this;
    }

    /**
     * Обработчик автозагрузки.
     * @param string имя класса или интерфейса
     */
    public function autoload(string $className)
    {
        foreach ($this->namespaces as $name => $path) {
            if (preg_match("/^$name(?<class>.*)/", $className, $matches)) {
                if ($this->load($path . $matches['class'] . '.php')) {
                    return;
                }
            }
        }
        foreach ($this->dirs as &$dir) {
            if ($this->load($dir . $className . '.php')) {
                return;
            }
        }
    }

    /**
     * Загрузка файла.
     * @param string имя файла
     * @return bool удалось ли загрузить
     */
    public function load(string $filename): bool
    {
        $filename = str_replace('\\', '/', $filename);
        $filename = "$this->baseDir$filename";
        if (is_readable($filename)) {
            include $filename;
            return true;
        }
        return false;
    }
}
