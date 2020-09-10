<?php
/**
 * @package evas-php\evas-loader
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
        if (empty($baseDir)) $baseDir = RunDirHelper::getDir();
        $this->baseDir($baseDir);
    }

    /**
     * Установка базовой директории.
     * @param string
     * @return self
     */
    public function baseDir(string $dir): Loader
    {
        $this->baseDir = RunDirHelper::prepareDir($dir);
        return $this;
    }

    /**
     * Установка директории/директорий поиска.
     * @param string директория
     * @return self
     */
    public function dir(string ...$dirs): Loader
    {
        foreach ($dirs as &$dir) {
            $dir = $this->baseDir . str_replace('\\', '/', $dir);
        }
        $this->dirs = array_merge($this->dirs, $dirs);
        return $this;
    }

    /**
     * Установка пути пространства имен.
     * @param string ространство имен
     * @param string путь
     * @return self
     */
    public function namespace(string $namespace, string $path): Loader
    {
        $namespace = str_replace('\\', '\\\\', $namespace);
        $this->namespaces[$namespace] = $this->baseDir . $path;
        return $this;
    }

    /**
     * Установка путей пространств имен.
     * @param array пути пространств имен
     * @return self
     */
    public function namespaces(array $namespaces): Loader
    {
        foreach ($namespaces as $namespace => $path) {
            $this->namespace($namespace, $path);
        }
        return $this;
    }

    /**
     * Добавление пространств имен компонентов evas-php.
     * @throws Exception
     * @return self
     */
    public function useEvas(): Loader
    {
        $evasDir = dirname(dirname(__DIR__));
        $dirs = scandir($evasDir);
        foreach ($dirs as &$dir) {
            if (0 === strpos($dir, 'evas-')) {
                $name = explode('-', $dir);
                foreach ($name as &$sub) {
                    $sub = ucfirst($sub);
                }
                $this->namespace(
                    (implode('\\', $name) . '\\'), 
                    "vendor/evas-php/$dir/src/"
                );
            }
        }
        return $this;
    }


    /**
     * Запуск автозагрузки.
     * @return self
     */
    public function run(): Loader
    {
        spl_autoload_register([$this, 'autoload']);
        return $this;
    }

    /**
     * Остановка автозагрузки.
     * @return self
     */
    public function stop(): Loader
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
        if (is_readable($filename)) {
            include $filename;
            return true;
        }
        return false;
    }
}
