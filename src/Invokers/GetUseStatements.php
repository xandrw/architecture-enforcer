<?php

namespace Xandrw\ArchitectureEnforcer\Invokers;

class GetUseStatements
{
    public function __invoke(string $fileContents): array
    {
        $useStatements = [];
        $tokens = token_get_all($fileContents);
        $inNamespaceDeclaration = false;

        foreach ($tokens as $token) {
            if (!is_array($token)) {
                if ($inNamespaceDeclaration && $token === ';') {
                    $inNamespaceDeclaration = false;
                }
                continue;
            }

            [$tokenId, $tokenContent, $line] = $token;

            if ($tokenId === T_NAMESPACE) {
                $inNamespaceDeclaration = true;
                continue;
            }

            if ($inNamespaceDeclaration && in_array($tokenId, [T_NAME_FULLY_QUALIFIED, T_NAME_QUALIFIED], true)) {
                continue;
            }

            if (in_array($tokenId, [T_NAME_FULLY_QUALIFIED, T_NAME_QUALIFIED], true)) {
                $tokenContent = ltrim($tokenContent, '\\');
                $useStatements[] = [trim($tokenContent), $line];
            }
        }

        return $useStatements;
    }
}
