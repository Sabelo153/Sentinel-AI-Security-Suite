<#
 .SYNOPSIS
 Adds concise purpose headers to first-party executable files.

 .DESCRIPTION
 Preserves existing documentation and skips dependencies, generated data, secrets,
 and runtime directories so it can be safely rerun as the repository grows.
#>

[CmdletBinding()]
param(
    [string]$Root = (Split-Path -Parent $PSScriptRoot)
)

$excludedDirectories = @(
    '.git', 'vendor', 'node_modules', '.safe-test-mariadb', '.tmp-mariadb',
    'storage', 'backups', 'logs', 'cache', 'uploads', 'coverage', 'dist', 'build'
)
$extensions = @('.php', '.sql', '.js', '.ts', '.css', '.html', '.htm', '.ps1', '.sh')
$changedFiles = [System.Collections.Generic.List[string]]::new()

function Get-FilePurpose {
    param([System.IO.FileInfo]$File, [string]$Content)

    $baseName = [System.IO.Path]::GetFileNameWithoutExtension($File.Name)
    if ($File.FullName -match '[\\/]tests[\\/]') {
        return "Tests the behavior covered by $baseName."
    }

    if ($File.Extension -eq '.sql') {
        return "Defines the database changes in $baseName."
    }

    if ($File.Extension -eq '.php') {
        $type = [regex]::Match($Content, '\b(?:final\s+|abstract\s+)?(?:class|interface|trait|enum)\s+(?<name>[A-Za-z_][A-Za-z0-9_]*)')
        if ($type.Success) {
            return "Defines the $($type.Groups['name'].Value) component."
        }

        if ($File.DirectoryName -match '[\\/]config(?:[\\/]|$)') {
            return "Provides configuration for $baseName."
        }
    }

    return "Provides the $baseName implementation."
}

function Add-Header {
    param([System.IO.FileInfo]$File)

    $content = [System.IO.File]::ReadAllText($File.FullName)
    $purpose = Get-FilePurpose -File $File -Content $content

    switch ($File.Extension) {
        '.php' {
            if ($content -match '^<\?php\s*\R\s*/\*\*' -or $content -match '^<\?php\s*\R\s*//') { return }
            $header = "/**`r`n * $purpose`r`n */"
            if ($content -match '^(<\?php\s*\R\s*declare\(strict_types=1\);)') {
                $updated = [regex]::Replace($content, '^(<\?php\s*\R\s*declare\(strict_types=1\);)', "`$1`r`n`r`n$header", 1)
            } else {
                $updated = [regex]::Replace($content, '^(<\?php)', "`$1`r`n`r`n$header", 1)
            }
        }
        '.sql' { if ($content -match '^\s*--') { return }; $updated = "-- $purpose`r`n`r`n$content" }
        { $_ -in '.html', '.htm' } { if ($content -match '^\s*<!--') { return }; $updated = "<!-- $purpose -->`r`n$content" }
        '.css' { if ($content -match '^\s*/\*') { return }; $updated = "/* $purpose */`r`n`r`n$content" }
        '.ps1' { if ($content -match '^\s*<#' -or $content -match '^\s*#') { return }; $updated = "# $purpose`r`n`r`n$content" }
        '.sh' { if ($content -match '^#!|^\s*#') { return }; $updated = "# $purpose`n`n$content" }
        default { if ($content -match '^\s*//|^\s*/\*') { return }; $updated = "// $purpose`r`n`r`n$content" }
    }

    [System.IO.File]::WriteAllText($File.FullName, $updated, [System.Text.UTF8Encoding]::new($false))
    $script:changedFiles.Add($File.FullName)
}

$excludedPattern = '[\\/](?:' + (($excludedDirectories | ForEach-Object { [regex]::Escape($_) }) -join '|') + ')(?:[\\/]|$)'
$files = Get-ChildItem -Path $Root -Recurse -File | Where-Object {
    $_.Extension -in $extensions -and
    $_.FullName -notmatch '\\.env(?:\.|$)' -and
    $_.FullName -notmatch $excludedPattern
}

foreach ($file in $files) {
    Add-Header -File $file
}

$changedFiles | ForEach-Object { Write-Output $_ }
Write-Output "Documented $($changedFiles.Count) files."