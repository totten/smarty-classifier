{* Intro comment *}

<p>Intro prose!</p>

{* Expression, no modifier            *}  {$var}
{* Expression, 1x modifier            *}  {$var|json_encode}
{* Expression, 1x @modifier           *}  {$var|@json_encode}
{* Expression, 2x modifier            *}  {$var|json_encode:16|escape}
{* Expression, 2x modifier, spaces    *}  {$var | json_encode : 16 | escape }
{* Directive, no params               *}  {show}
{* Directive, 1x param                *}  {show foo=bar}
{* Directive, 2x params               *}  {show foo=100 bar=200}
{* Literal                            *}  {literal}Say {stuff} {/literal}

{crmAPI var='caseTypes' entity='CaseType' action='get' option_limit=0 sequential=0}