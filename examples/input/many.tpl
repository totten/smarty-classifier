{* Intro comment *}

<p>Intro prose!</p>

{* Expression, no modifier            *}  {$var}
{* Expression, nofilter               *}  {$var nofilter}
{* Expression, 1x modifier            *}  {$var|json_encode}
{* Expression, 1x @modifier           *}  {$var|@json_encode}
{* Expression, 1x @modifier           *}  {$var|@json_encode nofilter}
{* Expression, 2x modifier            *}  {$var|json_encode:16|escape}
{* Expression, 2x modifier            *}  {$var|json_encode:16|escape nofilter}
{* Block, no params                   *}  {show}
{* Block, 1x param                    *}  {show foo=bar}
{* Block, 1x param, quotes            *}  {show foo="bar"}
{* Block, 2x params                   *}  {show foo=100 bar=200}
{* Block, 2x params, quotes           *}  {show foo='100' bar="200"}
{* Block  4x, mixed                   *}  {crmAPI var='caseTypes' entity='CaseType' action='get' option_limit=0 sequential=0}
{* Block, backquotes                  *}  {section name=count start=1 loop=`$maxMapper`}
{* Literal                            *}  {literal}Say {stuff} {/literal}
{* Squiggle A                         *}  foo { bar
{* Squiggle B                         *}  foo{bar
{* Squiggle C                         *}  {{show}
{* Translate, basic                   *}  {ts}Hello world{/ts}
{* Translate, param                   *}  {ts 1="Bob"}Hello %1{/ts}
{* Translate, param                   *}  {ts 1=$contact.display_name}Hello %1{/ts}
{* Ternary                            *}  {$bool ? $onTrue : $onFalse}
{* Function                           *}  {if !empty($data)}{/if}
{* Math!                              *}  {$var + 2}
{* Math!                              *}  {if $foo * 2 > 13.5}
{* Malformed                          *}  {-malformed-}
