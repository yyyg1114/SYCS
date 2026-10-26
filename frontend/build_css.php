<?php
// frontend/build_css.php

function checkAndBuildCss()
{
    $bundleFile = __DIR__ . '/css/bundle.min.css';
    $cacheDir = dirname(__DIR__) . '/backend/cache';
    $checkFlag = $cacheDir . '/css_check.flag';

    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }

    // 5秒以内ならmtimeチェック自体をスキップ
    if (file_exists($bundleFile) && file_exists($checkFlag) && (time() - filemtime($checkFlag)) < 5) {
        return;
    }

    $cssFiles = [
        __DIR__ . '/css/base.css',
        __DIR__ . '/css/layout.css',
        __DIR__ . '/css/components.css',
        __DIR__ . '/css/modals.css',
        __DIR__ . '/css/indicators.css',
        __DIR__ . '/css/markdown.css',
        __DIR__ . '/css/map.css',
        __DIR__ . '/css/widgets.css',
    ];

    $shouldBuild = !file_exists($bundleFile);
    if (!$shouldBuild) {
        $bundleMtime = filemtime($bundleFile);
        foreach ($cssFiles as $file) {
            if (file_exists($file) && filemtime($file) > $bundleMtime) {
                $shouldBuild = true;
                break;
            }
        }
    }

    if ($shouldBuild) {
        $bundleContent = '';
        foreach ($cssFiles as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                // 簡易ミニファイ（コメントの削除、連続する空白の削減など）
                $content = preg_replace('!/\*[^*]*\*+([^/*][^*]*\*+)*/!', '', $content); // コメント削除
                $content = str_replace(["\r\n", "\r", "\n", "\t"], ' ', $content); // 改行とタブをスペースに置換
                $content = preg_replace('!\s+!', ' ', $content); // 連続するスペースを1つに
                $content = str_replace('{ ', '{', $content);
                $content = str_replace(' }', '}', $content);
                $content = str_replace('; ', ';', $content);
                $content = str_replace(', ', ',', $content);
                $content = str_replace(': ', ':', $content);

                $bundleContent .= trim($content) . "\n";
            }
        }
        file_put_contents($bundleFile, trim($bundleContent));
    }

    // チェック完了フラグの更新
    file_put_contents($checkFlag, time());
}

// 実行する
checkAndBuildCss();
