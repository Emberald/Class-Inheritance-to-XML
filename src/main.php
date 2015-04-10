<?php

require('libXML.php');

const pattern = '/(?<!\bstatic class )(?<=\bclass )[a-zA-Z][a-z0-9A-Z]*(?=( *\n *\{)|( *\{ *\n)|( extends )|( implements ))/';

class Node {
    public static $counterID = 0;
    public $id;
    public $self;
    public $parent;
    public $child;

    public function __construct($self) {
        $this->self = $self;
        $this->child = false;
        $this->parent = -1;
        $this->id = Node::$counterID++;
    }

    public function appendChild(&$child) {
        if (!is_array($this->child)) {
            $this->child = array();
        }
        array_push($this->child, $child);
    }
}

function searchInFile($file, &$nodeList) {
    $contents = file_get_contents($file);
    if (preg_match_all(pattern, $contents, $self, PREG_SET_ORDER)) {
        $node = new Node($self[0][0]);
        $pattern2 = '/(?<=\b'.$self[0][0].' extends )[a-zA-Z][a-z0-9A-Z]*(?=( *\n *\{)|( *\{ *\n)|( implements ))/';
        if (preg_match_all($pattern2, $contents, $parent, PREG_SET_ORDER)) {
            $node->parent = $parent[0][0];
        }
        array_push($nodeList, $node);
    }
}

function listdiraux($dir, &$files) { 
    $handle = opendir($dir); 
    while (($file = readdir($handle)) !== false) { 
        if ($file == '.' || $file == '..') { 
            continue; 
        } 
        $filepath = $dir == '.' ? $file : $dir . '/' . $file; 
        if (is_link($filepath)) 
            continue; 
        if (is_file($filepath)) 
            $files[] = $filepath; 
        else if (is_dir($filepath)) 
            listdiraux($filepath, $files); 
    } 
    closedir($handle); 
} 

$pathIn = $argv[1];

$files = array(); 
$nodeList = array();
$nodeIndependent = array();

listdiraux($pathIn, $files);  
if (!is_dir(__DIR__.'/output')) {
    mkdir(__DIR__.'/output');
}

foreach ($files as $f) { 
	searchInFile($f, $nodeList);
} 

foreach ($nodeList as &$nodeMatcher) {
    foreach ($nodeList as &$nodeTarget) {
        if ($nodeTarget->self === $nodeMatcher->parent) {
            $nodeTarget->appendChild($nodeMatcher);
            $nodeMatcher->parent = $nodeTarget->id;
            break;
        }
    }
    if (is_string($nodeMatcher->parent)) { //check classes that extend superclasses outside the project
        $nodeMatcher->parent = -1;
    }
}

makeGraphXML($nodeList);

foreach ($nodeList as $nodePick) {
    if ($nodePick->parent === -1) {
        if ($nodePick->child === false) {
            array_push($nodeIndependent, $nodePick);
            unset($nodeList[$nodePick->id]);
        }
    }
    else {
        unset($nodeList[$nodePick->id]);
    }
}
sort($nodeList);

makeSimpleXML($nodeList);
makeTreeXML($nodeList);
makeReadable($nodeList, $nodeIndependent); 

 ?>