$cwebp = "C:\Users\HELMI\.gemini\antigravity\brain\e5826abc-2dcd-467c-ad98-a84add9d9549\scratch\webp_tools\libwebp-1.3.2-windows-x64\bin\cwebp.exe"
$images = Get-ChildItem -Path "images" -Filter *.png
$totalOriginal = 0
$totalNew = 0

foreach ($img in $images) {
    $originalPath = $img.FullName
    $originalSize = $img.Length
    $totalOriginal += $originalSize
    
    $newName = $img.Name -replace '\.png$', '.webp'
    $newPath = Join-Path -Path $img.DirectoryName -ChildPath $newName
    
    Write-Host "Compressing $($img.Name)..."
    
    # Run cwebp with quality 80 (-q 80) and -quiet to reduce output
    & $cwebp -q 80 -quiet $originalPath -o $newPath
    
    $newSize = (Get-Item $newPath).Length
    $totalNew += $newSize
    
    $saved = $originalSize - $newSize
    $percent = [math]::Round(($saved / $originalSize) * 100, 1)
    
    Write-Host "Done: $([math]::Round($originalSize/1KB, 1)) KB -> $([math]::Round($newSize/1KB, 1)) KB (-$percent%)"
    
    # Optional: If you want to delete original PNG right away
    # Remove-Item $originalPath
}

Write-Host "--- SUMMARY ---"
Write-Host "Original Total: $([math]::Round($totalOriginal/1MB, 2)) MB"
Write-Host "Compressed Total: $([math]::Round($totalNew/1MB, 2)) MB"
Write-Host "Total Saved: $([math]::Round(($totalOriginal - $totalNew)/1MB, 2)) MB"
