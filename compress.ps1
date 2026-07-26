Add-Type -AssemblyName System.Drawing

$images = Get-ChildItem -Path "images" -Filter *.png
$maxDimension = 1000
$totalSaved = 0

foreach ($imgFile in $images) {
    $filePath = $imgFile.FullName
    $originalSize = $imgFile.Length
    
    # Load image
    $img = [System.Drawing.Bitmap]::FromFile($filePath)
    
    $width = $img.Width
    $height = $img.Height
    
    if ($width -gt $maxDimension -or $height -gt $maxDimension) {
        # Calculate new dimensions
        $ratio = [Math]::Min($maxDimension / $width, $maxDimension / $height)
        $newWidth = [int]($width * $ratio)
        $newHeight = [int]($height * $ratio)
        
        Write-Host "Resizing $($imgFile.Name) from $($width)x$($height) to $($newWidth)x$($newHeight)..."
        
        # Create new bitmap
        $newImg = New-Object System.Drawing.Bitmap($newWidth, $newHeight)
        $newImg.SetResolution($img.HorizontalResolution, $img.VerticalResolution)
        
        # Create graphics object
        $graphics = [System.Drawing.Graphics]::FromImage($newImg)
        $graphics.CompositingQuality = [System.Drawing.Drawing2D.CompositingQuality]::HighQuality
        $graphics.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
        $graphics.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::HighQuality
        $graphics.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
        
        # Draw image
        $graphics.DrawImage($img, 0, 0, $newWidth, $newHeight)
        
        # Dispose graphics and original image
        $graphics.Dispose()
        $img.Dispose()
        
        # Save to temp file
        $tempPath = "$filePath.temp.png"
        $newImg.Save($tempPath, [System.Drawing.Imaging.ImageFormat]::Png)
        $newImg.Dispose()
        
        # Overwrite original
        Remove-Item $filePath
        Rename-Item $tempPath $filePath
        
        $newSize = (Get-Item $filePath).Length
        $saved = $originalSize - $newSize
        $totalSaved += $saved
        
        Write-Host "Done $($imgFile.Name): saved $([math]::Round($saved/1KB, 1)) KB"
    } else {
        $img.Dispose()
        Write-Host "Skipping $($imgFile.Name) (already small enough: $($width)x$($height))"
    }
}

Write-Host "Total space saved: $([math]::Round($totalSaved/1MB, 2)) MB"
