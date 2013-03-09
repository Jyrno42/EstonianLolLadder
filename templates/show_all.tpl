{extends file="index.tpl"}
{block name="content"}

{strip}
<div class="container-fluid">
<div class="row-fluid">
    <div class="title span{if $More}12{else}8{/if}">
        <div class="pull-left dropdown">
            <button class="btn dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
            <ul class="dropdown-menu" role="menu" aria-labelledby="dLabel">
                <li><a tabindex="-1" href="API.php?action=render&filter=euw&more={$More}">EUW</a></li>
                <li><a tabindex="-1" href="API.php?action=render&filter=eune&more={$More}">EUNE</a></li>
                <li><a tabindex="-1" href="API.php?action=render&more={$More}">All</a></li>
                <li class="divider"></li>
                <li><a tabindex="-1" href="API.php?action=render&showtop=1">Top 5</a></li>
            </ul>
        </div>
        {$Label} TOP
        <div class="pull-right dropdown">
            <button class="btn dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
            <ul class="dropdown-menu" role="menu" aria-labelledby="dLabel">
                <li><a tabindex="-1" href="API.php?action=render&filter={$Filter}&more={!$More}">Show {if $More}Less{else}More{/if}</a></li>
            </ul>
        </div>
    </div>
</div>
<div class="row-fluid">
    <div class="span{if $More}12{else}8{/if}">
        <table class="table tablesorter" id="myTable" style="margin-bottom: 0px;">
            <thead>
                <tr>
                    <th class="span1">#</th>
                    <th class="span{if $More}1{else}3{/if}">Nimi</th>
                    <th class="span1">Server</th>
                    <th class="span1">Skoor</th>
                    <th class="span1">Liiga</th>
                    <th class="span1">Võidud</th>
                    <th class="span{if $More}2{else}4{/if}">Lost</th>
                    {if $More}
                        <th class="span1">KDA</th>
                        <th class="span1">KillRecord</th>
                        <th class="span1">Quadra's</th>
                        <th class="span1">Penta's</th>
                    {/if}
                </tr>
            </thead>
            <tbody>
                {foreach from=$Summoners item=Summoner name=foo}
                    {if $Summoner->Tier != 0}
                    <tr>
                        <td>{$smarty.foreach.foo.iteration}</td>
                        <td>
                            <a href="#" role="button" class="summoner-name" data-summoneraid="{$Summoner->AID}">{$Summoner->Name|utf8_encode}</a>
                        </td>
                        <td>{$Summoner->Region|strtoupper}</td>
                        <td>{$Summoner->get_estimated_elo()}</td>
                        <td data-rval="{$Summoner->get_estimated_elo()}">{$Summoner->TierName()} {$Summoner->RankName()} {$Summoner->LeaguePoints} LP {if $Summoner->League}<br>{$Summoner->League}{/if}</td>
                        <td>{$Summoner->WON}</td>
                        <td>{$Summoner->LOST}</td>
                        {if $More}
                            <td>
                                {(($Summoner->Kills+$Summoner->Assists)/($Summoner->Deaths))|string_format:"%.2f"|default:"0"}
                            </td>
                            <td>{$Summoner->MaxChampionKills}</td>
                            <td>{$Summoner->QuadraKills}</td>
                            <td>{$Summoner->PentaKills}</td>
                        {/if}
                    </tr>
                    {/if}
                {/foreach}
            </tbody>
        </table>
    </div>
</div>
<div class="row-fluid">
    <div class="title span{if $More}12{else}8{/if} clearfix">
        <small>
            {nocache}<a href="#" title="{$UpdateLog|default:""}" class="pull-right my-tooltip">Viimane uuendus {relative_time($Update)}</a>{/nocache}
        </small>
    </div>
</div>
</div>
{/strip}
{/block}