# genstats

**genstats** provides a tool for generating well-typed enum codes
corresponding to the BGA `stats.json` file.

## Generation

Generate the Stats class via

```sh
$ php genstats.php gamename > modules/php/Stats.php
```

(assumes the current directory is the main game directory where
`stats.json` is located).

The generated `Stats.php` file wil contain definitions of backed enums
for each statistic defined in `stats.json`, with the enum name derived
from the `stats.json` identifiers, and with typed methods for the
different kinds of stats (table / player, int / float / bool).

## Usage in game code

### Configuration

Insert one line of configuration into the `Game.php` constructor:

```php

   private Stats $stats;

   public function __construct() {
       ...
       $this->stats = new Stats($this);
       ...
   }
```

### Initialization

Initialize stats in `setupNewGame()`:

```php
    protected function setupNewGame($players, $options = []) {
       ...
       // Initialize all stats to "zero" values
       $this->stats->initAll(array_keys($players));

       // Or, initialize each stat individually:
       $this->stats->PLAYER_MY_FLOAT_STAT->init(array_keys($players), 1.732);
       Sthis->stats->PLAYER_MY_BOOL_STAT->init(array_keys($players), true);

       // Or, different value per player id:
       $this->stats->PLAYER_MY_OTHER_INT_STAT->initMap($array_keys($players),
           function ($pid) { return rand(0, 4); });

       // Table stats are simpler, only one possible init:
       Stats::TABLE_MY_FLOAT_STAT->init(3.14159);
       ...
    }
```

### Updating / accessing stats (anywhere):

```php
   ...
   $stats->PLAYER_NUMBER_TURNS->inc($player_id);
   $stats->TABLE_GAME_ENDED_DUE_TO_PIECE_EXHAUSTION->set(true);
   $stats->TABLE_OTHER_FLOAT->set(1.15, $stats->TABLE_OTHER_FLOAT->get());
   ...
```

## TODOs:

 * improve `toIdentifier()`.
 * determine if need to include the BGA license boilerplate in the
   generated code.
 * let the Stats class name be customized?
 *
