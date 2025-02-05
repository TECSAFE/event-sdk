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
        if ($node instanceof \PhpParser\Node\Stmt\Class_) {
            $factory = new \PhpParser\BuilderFactory();
            $newConstNodes = [];

            // Neue Konstanten als Nodes erstellen
            foreach ($this->events as $event) {
                $newConstNodes[] = $factory->classConst($this->createEventConstantName($event['name']), $event)->getNode();
            }

            // Konstanten vor der ersten vorhandenen Konstante einfügen
            foreach ($node->stmts as $index => $stmt) {
                if ($stmt instanceof \PhpParser\Node\Stmt\ClassConst) {
                    array_splice($node->stmts, $index, 0, $newConstNodes);
                    return;
                }
            }

            // Falls keine vorhandene Konstante existiert, einfach hinzufügen
            array_push($node->stmts, ...$newConstNodes);
        }

        if ($node instanceof \PhpParser\Node\Const_ && $node->name->toString() === 'EVENTS') {
            $builder = new \PhpParser\BuilderFactory();
            $eventsArray = [];
            //$arrayNode = $builder->val($this->events);
            /* @var $newConstNode \PhpParser\Builder\ClassConst */
            foreach ($this->events as $event) {
                $eventsArray[] = $builder->classConstFetch('self', $this->createEventConstantName($event['name']));
            }

            $arrayNode = $builder->val($eventsArray);
            $node->value = $arrayNode;
        }
    }

    private function createEventConstantName(string $eventName): string
    {
        $array = explode('.', $eventName);

        return \strtoupper(\implode('_', $array));
    }
});

$modifiedStmts = $traverser->traverse($ast);

$prettyPrinter = new \PhpParser\PrettyPrinter\Standard();

$newCode = $prettyPrinter->prettyPrintFile($modifiedStmts);

$fileObj = $fileInfo->openFile('w+');

$fileObj->fwrite($newCode);
