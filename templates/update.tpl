# Generated at: {$DATE}

now=$(date +"%T")

echo "UPDATING AT: $now"

echo "Kill old workers."
pkill php

echo "Start Workers"

{foreach from=$Workers item=Worker name=foo}
php -f {$DIR}API.php {$Worker->start} {$Worker->amount} > {$LOGDIR}output{$smarty.foreach.foo.iteration}.log &
{/foreach}