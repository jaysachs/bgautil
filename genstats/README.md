## Generation

Generate the Stats class via

```sh
$ php genstats.php gamename > modules/php/Stats.php
```

(assumes the current directory is the main game directory where
stats.json is located).

The `Stats.php` file wil contain backed enums for each statistic defined
in `stats.json`, with the enum name derived from the stats.json identifier,
and with typed methods for the different kinds of stats (table / player,
int / float / bool).

## Usage in game code

### Configuration

Insert one line of configuration into the Game.php constructor:

```php
   public function __construct() {
       ...
       Stats::init($this);
       ...
   }
```

### Initialization

Initialize stats in setupNewGame():

```php
    protected function setupNewGame($players, $options = []) {
       ...
       // Initialize all stats to "zero" values
       Stats::initAll(array_keys($players));

       // Or, initialize each stat individually:
       Stats::PLAYER_MY_FLOAT_STAT->initAll(array_keys($players), 1.732);
       Stats::PLAYER_MY_BOOL_STAT->initAll(array_keys($players), true);

       // Or, different value per player id:
       foreach ($players as $player_id => $player) {
           Stats::PLAYER_MY_INT_STAT->init($player_id, rand(0, 4));
       }
       // or, alternatively use initMap():
       Stats::PLAYER_MY_OTHER_INT_STAT->initMap($array_keys($players),
           function ($pid) { return rand(0, 4); });

       // Table stats are simpler, only one possible init:
       Stats::TABLE_MY_FLOAT_STAT->init(3.14159);
       ...
    }
```

### Updating / accessing stats (anywhere):

```php
   ...
   Stats::PLAYER_NUMBER_TURNS->inc($player_id);
   Stats::TABLE_GAME_ENDED_DUE_TO_PIECE_EXHAUSTION->set(true);
   Stats::TABLE_OTHER_FLOAT->set(1.15Stats::TABLE_OTHER_FLOAT->get());
   ...
```

## TODOs:

 * improve `toIdentifier`.
 * determine if need to include the BGA license boilerplate in the
   generated code.
 * let the Stats class name be customized?
