#

WORKDIR=work
STATS=$(WORKDIR)/stats.json
PHPSTATS=$(WORKDIR)/Stats.php

clean:
	rm -rf $(WORKDIR)

$(WORKDIR):
	mkdir $(WORKDIR)

$(STATS): $(WORKDIR) teststats.json genstats.php
	cp teststats.json $(STATS)

$(PHPSTATS): $(STATS)
	cd $(WORKDIR) && php ../genstats.php testgame > Stats.php

test: $(PHPSTATS)
	cd $(WORKDIR) && phpunit --bootstrap ../autoload.php ../StatsTest.php --testdox --display-warnings --display-deprecations
