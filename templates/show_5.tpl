{extends file="index.tpl"}

{block name="content"}

    <table class="table" style="width: 240px; margin-bottom: 0px;">
        <thead>
            <tr>
                <th colspan="7" class="title">
                    <a href="http://lol.th3f0x.com/API.php">{$Label} TOP 5</a>
                    <div class="pull-left dropdown">
                        <button class="btn dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
                        <ul class="dropdown-menu" role="menu" aria-labelledby="dLabel">
                            <li><a tabindex="-1" href="API.php?action=render&filter=euw&showtop=1">EUW Top 5</a></li>
                            <li><a tabindex="-1" href="API.php?action=render&filter=eune&showtop=1">EUNE Top 5</a></li>
                            <li><a tabindex="-1" href="API.php?action=render&showtop=1">Top 5</a></li>
                            <li class="divider"></li>
                            <li><a tabindex="-1" href="API.php?action=render">Show All</a></li>
                        </ul>
                    </div>
                </th>
            </tr>
            <tr>
                <th class="span1">#</th>
                <th class="span7">Nimi</th>
                <th class="span2">Server</th>
                <th class="span2">Skoor</th>
            </tr>
        </thead>
        <tbody>
            {foreach from=$Summoners item=Summoner name=foo}
                {if $Summoner->Tier != 0}
                <tr>
                    <td>{$smarty.foreach.foo.iteration}</td>
                    <td>
                        {$Summoner->Name}
                    </td>
                    <td>{$Summoner->Region|strtoupper}</td>
                    <td>{$Summoner->Score}</td>
                </tr>
                {/if}
            {/foreach}
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4">
                    <small>
                        {nocache}<a href="#" title="{$UpdateLog|default:""}" class="pull-right my-tooltip">Viimane uuendus {relative_time($Update)}</a>{/nocache}
                    </small>
                </th>
            </tr>
        </tfoot>
    </table>

{/block}