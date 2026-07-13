<?php
/**
 * 為了達成您**「將繁體中文驗證訊息統一在 shared 套件中管理」**的需求，我們必須打破上述限制。
 *
 * MixedFileLoader.php 繼承了 Laravel 原生的  FileLoader ，並做到了以下兩件事：
 *
 * 1. 多路徑支援：允許註冊額外的自訂語言檔路徑（在此註冊了  shared/common/lang ）。
 * 2. 多路徑合併（Fallback & Merge）：當框架要求載入無命名空間的  validation  語系時，它會同時載入「套件預設值」與「本機專案設定」，並進行合併。
 */
namespace Shared\Translation;
 
use Illuminate\Translation\FileLoader;
 
class MixedFileLoader extends FileLoader
{
    protected array $customPaths = [];
 
    public function addPath($path)
    {
        $this->customPaths[] = $path;
    }
 
    /**
     * Load the messages for the given locale.
     *
     * @param  string  $locale
     * @param  string  $group
     * @param  string|null  $namespace
     * @return array
     */
    public function load($locale, $group, $namespace = null)
    {
        // First load from the default application path (parent loader)
        $lines = parent::load($locale, $group, $namespace);
 
        // If namespace-less (or namespace is '*'), also load and merge from custom paths
        if (empty($namespace) || $namespace === '*') {
            foreach ($this->customPaths as $path) {
                if ($this->files->exists($full = "{$path}/{$locale}/{$group}.php")) {
                    $customLines = $this->files->getRequire($full);
                    $lines = array_replace_recursive($customLines, $lines);
                }
            }
        }
 
        return $lines;
    }
}
