#!/usr/bin/env php
<?php
/**
 * a converter from md to html
 *
 * Usage $0 <file.md>
 */
require_once __DIR__ . '/vendor/autoload.php';


global $argv;
$file = $argv[1];

// use github markdown

// this is broken
//$parser = new \cebe\markdown\GithubMarkdown();
//echo $parser->parse(file_get_contents($file));

$Parsedown = new Parsedown();
$mdContent = file_get_contents($file);
$html = $Parsedown->text($mdContent);

$html = preg_replace('/\.md/', '.html', $html);

$config['title'] = "Ethnamドキュメント";
$config['basename'] = basename($file, '.md');

$partialDir = __DIR__ . '/partial';

require_once $partialDir . '/_header.html';

echo $html;

require_once $partialDir . '/_footer.html';
