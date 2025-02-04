<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

$schema = \json_decode(file_get_contents(__DIR__ . '/../../dist/schema.json'), true);

$eventMap = $schema['definitions']['EventsMap']['properties'];

$events = [];

foreach ($eventMap as $key => $event) {
    $events[] = [
        'name' =>  $key,
        'type' => $event['const'],
    ];
}

$fileInfo = new \SplFileInfo(__DIR__ . '/../../php/EventMap.php');
$parser = (new \PhpParser\ParserFactory())->createForVersion(\PhpParser\PhpVersion::fromComponents(8, 3));

try {
    $ast = $parser->parse(\file_get_contents($fileInfo->getRealPath()));

} catch (Error $error) {
    echo "Parse error: {$error->getMessage()}\n";
    return;
}

$traverser = new \PhpParser\NodeTraverser();

$traverser->addVisitor(new class($events) extends \PhpParser\NodeVisitorAbstract {
    public function __construct(private readonly array $events) {}

    public function enterNode(\PhpParser\Node $node) {
        if ($node instanceof \PhpParser\Node\Const_ && $node->name->toString() === 'EVENTS') {
            $builder = new \PhpParser\BuilderFactory();

            $arrayNode = $builder->val($this->events);

            $node->value = $arrayNode;
        }
    }
});

$modifiedStmts = $traverser->traverse($ast);

$prettyPrinter = new \PhpParser\PrettyPrinter\Standard();

$newCode = $prettyPrinter->prettyPrintFile($modifiedStmts);

$fileObj = $fileInfo->openFile('w+');

$fileObj->fwrite($newCode);