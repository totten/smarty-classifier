{ts 1=$name}Hello %1{/ts}
{ts 1 = $name}Greetings %1{/ts}
{ts 1=$name|escape}Hello %1{/ts}
{ts 1=$name|escape:html}Hello %1{/ts}
{ts 1=$name|escape:"html" 2=$name|escape:"url"}Hello %1 %2{/ts}
{ts 1=$name|escape : "html" 2=$name|escape : "url"}Hello %1 %2{/ts}
{ts 1 = $name | escape:"html" 2 = $name | escape : "url"}Hello %1 %2{/ts}
