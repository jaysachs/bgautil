<?php

/**
 * gvstates -- generate DOT file for a BGA game state machine
 *
 * Usage:
 *
 *   php gvstates.php /path/to/states.inc.php
 *
 * emits the generated dotfile to stdout. If the states file has implicit
 * dependencies, you can specify them in order before the states file:
 *
 *   php gvstates.php include1.php include2.php states.inc.php
 *
 * A fuller example including conversion to image:
 *
 *   php gvstates.php states.inc.php | dot -T png > /tmp/mygamestates.png
 *
 * For documentation on Graphviz and DOT, see https://graphviz.org/
 */

namespace Bga\GameFramework {

enum StateType: string {
    case MULTIPLE_ACTIVE_PLAYER = "multipleactiveplayer";
    case PRIVATE = "private";
    case ACTIVE_PLAYER = "activeplayer";
    case GAME = "game";
    case MANAGER = "manager";
}

class GameStateBuilder {

    private function __construct(private array $gamestate) {}

    public static function create(): GameStateBuilder {
        return new GameStateBuilder([]);
    }

    public function name(string $val): GameStateBuilder {
        $this->gamestate["name"] = $val;
        return $this;
    }

    public function type(StateType $val): GameStateBuilder {
        $this->gamestate["type"] = $val->value;
        return $this;
    }

    public function description(string $val): GameStateBuilder {
        $this->gamestate["description"] = $val;
        return $this;
    }

    public function descriptionmyturn(string $val): GameStateBuilder {
        $this->gamestate["descriptionmyturn"] = $val;
        return $this;
    }

    public function action(string $val): GameStateBuilder {
        $this->gamestate["action"] = $val;
        return $this;
    }

    public function args(string $val): GameStateBuilder {
        $this->gamestate["args"] = $val;
        return $this;
    }

    public function updateGameProgression(bool $val): GameStateBuilder {
        $this->gamestate["updateGameProgression"] = $val;
        return $this;
    }

    public function transitions(array $val): GameStateBuilder {
        $this->gamestate["transitions"] = $val;
        return $this;
    }

    public function possibleactions(array $val): GameStateBuilder {
        $this->gamestate["possibleactions"] = $val;
        return $this;
    }

    public function build(): array {
        return $this->gamestate;
    }
}

} // namespace

namespace {

use Bga\GameFramework\GameStateBuilder;
use Bga\GameFramework\StateType;


function clienttranslate(string $s): string { return $s; }

const EDGE_ATTRS = "fontname=Arial,decorate=false,";

const NODE_ATTRS = "fontname=Arial,";

function node(int $id, array $state): void {
    $shape = match ($state["type"]) {
        StateType::ACTIVE_PLAYER->value => 'parallelogram',
        StateType::MULTIPLE_ACTIVE_PLAYER->value => 'hexagon',
        StateType::PRIVATE->value => 'trapezium',
        StateType::GAME->value => 'box',
        StateType::MANAGER->value => match($id) {
            1 => 'invtriangle',
            99 => 'octagon',
        }
    };

    $label = sprintf("<table border=\"0\"><tr><td colspan=\"2\"><b>%s</b></td></tr>", $state["name"]);
    if (isset($state["args"])) {
        $label .= sprintf("<tr><td><i>args</i></td><td><font face=\"monospace\">%s</font></td></tr>", $state["args"]);
    }
    if (isset($state["action"])) {
        $label .= sprintf("<tr><td><i>act</i></td><td><font face=\"monospace\">%s</font></td></tr>", $state["action"]);
    }
    $label .= "</table>";
    echo sprintf("    %s [%sshape=%s,label=<%s>];\n", $state["name"], NODE_ATTRS, $shape, $label);
}

function edge(array $state, string $label, array $dest): void {
    echo sprintf("    %s -> %s [%slabel=\"%s\"];\n", $state["name"], $dest["name"], EDGE_ATTRS, $label);
}

function edges(int $id, array $states) {
    $state = $states[$id];
    if (isset($state["transitions"])) {
        foreach ($state["transitions"] as $label => $destid) {
            edge($state, $label, $states[$destid]);
        }
    }
}

function doState(int $id, array $states) {
    node($id, $states[$id]);
    edges($id, $states);
}

/** @param GameState[] $states */
function generateGraphViz(array $states): void { ?>
<?php
    if (!isset($states[1])) {
        $states[1] = GameStateBuilder::create()->name('START')->transitions(["" => 2])->type(StateType::MANAGER,)->build();
    }
    if (!isset($states[99])) {
        $states[99] = GameStateBuilder::create()->name('END')->transitions([])->type(StateType::MANAGER,)->build();
    }
?>
digraph {
    rankdir="TB"
    subgraph {
        rank=source
        <?php node(1, $states[1]); ?>
    }
    subgraph {
        rank=sink
        <?php node(99, $states[99]); ?>
    }
<?php
    foreach ($states as $id => $state) {
        if ($id == 1 || $id == 99) {
            edges($id, $states);
        } else {
            doState($id, $states);
        }
    }

?>
}
<?php
}

$file = "states.inc.php";
if (count($argv) < 2) {
    fwrite(STDERR, "Usage: php $argv[0] [ includefile ... ] statesfile\n");
    exit(1);
}
foreach (array_slice($argv, 1) as $_ => $file) {
  if (!include($file)) {
      fwrite(STDERR, "Unable to read $file\n");
      exit(1);
  }
}

generateGraphViz($machinestates);

} // namespace
?>
