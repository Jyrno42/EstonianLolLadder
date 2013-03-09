{extends file="exception.tpl"}

{block name="width"}100%{/block}
{block name="exception_label"}Whoops!{/block}
{block name="exception_text"}It appears there was a problem.{/block}
{block name="exception_raw"}{var_dump($error)}{/block}