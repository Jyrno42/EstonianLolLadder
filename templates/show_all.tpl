{extends file="show.html"}
{block name="show"}

{strip}
<div class="container-fluid">
<div class="row-fluid">
    <div class="title span12">
        <div class="pull-left dropdown">
            <button class="btn dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
            <ul class="dropdown-menu" role="menu" aria-labelledby="dLabel">
                <li><a tabindex="-1" href="API.php?action=render&filter=euw">EUW</a></li>
                <li><a tabindex="-1" href="API.php?action=render&filter=eune">EUNE</a></li>
                <li><a tabindex="-1" href="API.php?action=render">All</a></li>
                <li class="divider"></li>
                <li><a tabindex="-1" href="API.php?action=render&showtop=1">Top 5</a></li>
            </ul>
        </div>
        {$Label} TOP
    </div>
</div>
<div class="row-fluid">
    <div class="span12">
        <table class="table tablesorter" id="myTable" style="margin-bottom: 0px;">
            <thead>
                <tr>
                    <th class="span1">#</th>
                    <th class="span2">Nimi</th>
                    <th class="span1">Server</th>
                    <th class="span1">Skoor</th>
                    <th class="span1">Liiga</th>
                    <th class="span1">Võidud</th>
                    <th class="span1">Lost</th>
                    <th class="span1">KDA</th>
                    <th class="span1">KillRecord</th>
                    <th class="span1">Quadra's</th>
                    <th class="span1">Penta's</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$Summoners item=Summoner name=foo}
                    {if $Summoner->Tier != 0}
                    <tr>
                        <td>{$smarty.foreach.foo.iteration}</td>
                        <td>
                            {$Summoner->Name}
                            {if $Summoner->HotStreak} <i class="icon-fire"></i>{/if}
                            {if $Summoner->Veteran} <i class="icon-calendar"></i>{/if}
                        </td>
                        <td>{$Summoner->Region|strtoupper}</td>
                        <td>{$Summoner->Score}</td>
                        <td data-rval="{$Summoner->Score}">{$Summoner->TierName()} {$Summoner->RankName()} {$Summoner->LeaguePoints} LP {if $Summoner->League}<br>{$Summoner->League}{/if}</td>
                        <td>{$Summoner->WON}</td>
                        <td>{$Summoner->LOST}</td>
                        <td>
                            {(($Summoner->Kills+$Summoner->Assists)/($Summoner->Deaths))|string_format:"%.2f"|default:"0"}
                        </td>
                        <td>{$Summoner->MaxChampionKills}</td>
                        <td>{$Summoner->QuadraKills}</td>
                        <td>{$Summoner->PentaKills}</td>
                    </tr>
                    {/if}
                {/foreach}
            </tbody>
        </table>
    </div>
</div>
<div class="row-fluid">
    <div class="title span12 clearfix">
    
        <small>
            {nocache}<a class="pull-left my-tooltip update-log" href="#reportModal" role="button" data-toggle="modal">Viimane uuendus {relative_time($Update)}</a>{/nocache}
            {nocache}<span class="pull-right">v{constant("VERSION")} <a href="https://github.com/Jyrno42/LolUpdator"><img src="https://travis-ci.org/Jyrno42/LolUpdator.png" /></a><a href="http://mixpanel.com/f/partner"><img src="http://mixpanel.com/site_media/images/partner/badge_blue.png" alt="Mobile Analytics" /></a></span>{/nocache}
        </small>
    </div>
</div>
</div>
{/strip}
{/block}
