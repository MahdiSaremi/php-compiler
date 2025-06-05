<?php

return new class extends CustomLanguage {
    public function grammar(): string
    {
        $E = new \Comp\Grammar\NonTerminal();
        return <<<GRAMMAR
            $E => $E '+' $E { #add }
            $E => $E '-' $E { #sub }
            $E => $E '*' $E { #mul }
            $E => $E '/' $E { #div }
            $E => num { #num }
            
            WHEN {
                #add & #sub : left
                
            }
        GRAMMAR;
    }
};
