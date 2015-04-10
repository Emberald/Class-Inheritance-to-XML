<?php
	
//make a simple XML
function makeSimpleXML($nodeList) {
	$xml = new SimpleXMLElement("<Classes></Classes>");
	simpleArrayToXML($nodeList, $xml, 1);

	outputXML($xml, 'simple.xml');
}

function simpleArrayToXML($tree, &$xml, $depth) {
    foreach ($tree as $class) {
        $xmlSubclass = $xml->addChild('L'.$depth);
        $xmlSubclass->addAttribute('name', $class->self);
        if ($class->child !== false) {
            simpleArrayToXML($class->child, $xmlSubclass, $depth + 1);
        }
    }
}

// For prefuse tree view
function makeTreeXML($nodeList) {
	$rootNode = new Node("Inheriting Classes");
	$rootNode->child = &$nodeList;
	$rootNode->id = -1;
	$output = array();
	array_push($output, $rootNode);

	$xml = new SimpleXMLElement("<tree></tree>");
	$xmlSub = $xml->addChild("declarations");
	$xmlSubSub = $xmlSub->addChild("attributeDecl");
	$xmlSubSub->addAttribute('name', "name");
	$xmlSubSub->addAttribute('type', "String");

	arrayToTreeXML($output, $xml);

	outputXML($xml, 'forTreeViewAndTreeMap.xml');
}

function arrayToTreeXML($tree, &$xml) {
    foreach ($tree as $class) {
        if ($class->child !== false) {
            $xmlSubclass = $xml->addChild('branch');
            addAttri($xmlSubclass, $class->self);
            arrayToTreeXML($class->child, $xmlSubclass);
        }
        else {
            addLeaf($xml, $class->self);
        }
    }
}

function addLeaf(&$xml, $value) {
    $xmlSub = $xml->addChild("leaf");
    addAttri($xmlSub, $value);
}

function addAttri(&$xml, $value) {
    $xmlSub = $xml->addChild("attribute");
    $xmlSub->addAttribute("name", "name");
    $xmlSub->addAttribute("value", $value);
}

// For prefuse graph view
function makeGraphXML($nodeList) {
	$xml = new SimpleXMLElement('<graphml></graphml>');
	$xml->addAttribute("xmlns", 'http://graphml.graphdrawing.org/xmlns');
	$xmlSub = $xml->addChild("graph");
	$xmlSub->addAttribute("edgedefault", "undirected");

	addDataSchema($xmlSub, "name", "node", "name", "string");
	// addDataSchema($xmlSub, "size", "node", "size", "string");

	$rootNode = new Node("Inheriting Classes");
	$rootNode->id = -1;
	$rootNode->child = array();
	array_unshift($nodeList, $rootNode);

	foreach ($nodeList as $node) {
		if ($node->parent !== -1 || is_array($node->child)) {
			addNode($xmlSub, $node);
		}
	}

	foreach ($nodeList as $node) {
		if ($node->parent !== -1 || is_array($node->child)) {
			addEdge($xmlSub, $node);
		}
	}

	outputXML($xml, 'forGraphViewAndRadialGraphView.xml');
}

function addDataSchema(&$xml, $name, $for, $attriName, $attriType) {
    $xmlSub = $xml->addChild("key");
    $xmlSub->addAttribute("id", $name);
    $xmlSub->addAttribute("for", $for);
    $xmlSub->addAttribute('attr.name', $attriName);
    $xmlSub->addAttribute('attr.type', $attriType);
}

function addNode(&$xml, $node) {
    $xmlSub = $xml->addChild("node");
    $xmlSub->addAttribute("id", $node->id);

    $xmlSubSub = $xmlSub->addChild('data', $node->self);
    $xmlSubSub->addAttribute("key", "name");
}

function addEdge(&$xml, $node) {
    $xmlSub = $xml->addChild("edge");
    $xmlSub->addAttribute("source", $node->id);
    $xmlSub->addAttribute("target", $node->parent);
}

//output
function outputXML($xml, $filename) {
	$domxml = new DOMDocument('1.0');
	$domxml->preserveWhiteSpace = false;
	$domxml->formatOutput = true;
	$domxml->loadXML($xml->asXML());
	$domxml->save(__DIR__.'/output/'.$filename);
}

//others
function makeReadable($nodeList, $nodeIndependent) {
	file_put_contents(__DIR__.'/output/Readable.md', "###Inheriting classes\n");
	putInheritance($nodeList, 0);
	file_put_contents(__DIR__.'/output/Readable.md', "###Classes without any superclass in the project\n", FILE_APPEND);
	putIndependence($nodeIndependent);
}

function putInheritance($nodeList, $depth) {
	$numList = 1;
	$indentation = str_repeat('  ', $depth);
	$depth++;
    foreach ($nodeList as $node) {
    	file_put_contents(__DIR__.'/output/Readable.md', $indentation.$numList++.'. '.$node->self."\n", FILE_APPEND);
        if ($node->child !== false) {
            putInheritance($node->child, $depth);
        }
    }	
}

function putIndependence($nodeIndependent) {
	$numList = 1;
	foreach ($nodeIndependent as $node) {
    	file_put_contents(__DIR__.'/output/Readable.md', $numList++.'. '.$node->self."\n", FILE_APPEND);
	}
}

?>