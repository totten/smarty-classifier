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
{* Literal                            *}  {literal}Say {stuff} {/literal}
{* Squiggle A                         *}  foo { bar
{* Squiggle B                         *}  foo{bar
{* Squiggle C                         *}  {{show}

{crmAPI var='caseTypes' entity='CaseType' action='get' option_limit=0 sequential=0}