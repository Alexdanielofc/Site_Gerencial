[CmdletBinding()]
param(
    [string]$Initials = 'SG',
    [string]$Color = '#1d4ed8',
    [string]$ProjectDir = ''
)

$ErrorActionPreference = 'Stop'

if (-not $ProjectDir) {
    $ProjectDir = Split-Path -Path $PSScriptRoot -Parent
}

Add-Type -AssemblyName System.Drawing

$publicIconsDir = Join-Path $ProjectDir 'public\icons'
if (-not (Test-Path -LiteralPath $publicIconsDir)) {
    New-Item -ItemType Directory -Path $publicIconsDir -Force | Out-Null
}

$sizes = @(16, 32, 48, 64, 128, 256)
$pngStreams = @()

$bgBrush = New-Object System.Drawing.SolidBrush ([System.Drawing.ColorTranslator]::FromHtml($Color))
$textBrush = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::White)

foreach ($size in $sizes) {
    $bmp = New-Object System.Drawing.Bitmap ($size, $size, [System.Drawing.Imaging.PixelFormat]::Format32bppArgb)
    $g = [System.Drawing.Graphics]::FromImage($bmp)
    $g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
    $g.TextRenderingHint = [System.Drawing.Text.TextRenderingHint]::AntiAliasGridFit
    $g.Clear([System.Drawing.Color]::Transparent)

    # Rounded rectangle
    $radius = [math]::Max(2, [int]($size * 0.22))
    $rect = New-Object System.Drawing.Rectangle (0, 0, $size, $size)
    $path = New-Object System.Drawing.Drawing2D.GraphicsPath
    $diameter = $radius * 2

    $path.AddArc($rect.X, $rect.Y, $diameter, $diameter, 180, 90)
    $path.AddArc($rect.Right - $diameter, $rect.Y, $diameter, $diameter, 270, 90)
    $path.AddArc($rect.Right - $diameter, $rect.Bottom - $diameter, $diameter, $diameter, 0, 90)
    $path.AddArc($rect.X, $rect.Bottom - $diameter, $diameter, $diameter, 90, 90)
    $path.CloseFigure()

    $g.FillPath($bgBrush, $path)

    # Text SG
    $fontSize = [float]($size * 0.44)
    $fontFamily = New-Object System.Drawing.FontFamily ('Segoe UI')
    if (-not $fontFamily) {
        $fontFamily = [System.Drawing.FontFamily]::GenericSansSerif
    }
    $font = New-Object System.Drawing.Font ($fontFamily, $fontSize, [System.Drawing.FontStyle]::Bold, [System.Drawing.GraphicsUnit]::Pixel)

    $format = New-Object System.Drawing.StringFormat
    $format.Alignment = [System.Drawing.StringAlignment]::Center
    $format.LineAlignment = [System.Drawing.StringAlignment]::Center

    # Slight vertical offset for optical centering
    $textRect = New-Object System.Drawing.RectangleF (0, ($size * 0.04), $size, $size)
    $g.DrawString($Initials, $font, $textBrush, $textRect, $format)

    $ms = New-Object System.IO.MemoryStream
    $bmp.Save($ms, [System.Drawing.Imaging.ImageFormat]::Png)
    $pngStreams += @{
        Size = $size
        Bytes = $ms.ToArray()
    }

    $g.Dispose()
    $bmp.Dispose()
    $ms.Dispose()
}

$bgBrush.Dispose()
$textBrush.Dispose()

# Build multi-resolution ICO file
function Save-IcoFile ([string]$Path, [array]$Images) {
    $fs = New-Object System.IO.FileStream ($Path, [System.IO.FileMode]::Create)
    $bw = New-Object System.IO.BinaryWriter ($fs)

    # ICONDIR header
    $bw.Write([uint16]0) # Reserved
    $bw.Write([uint16]1) # Type 1 = ICO
    $bw.Write([uint16]$Images.Count) # Image count

    # Directory entries offset starts after header (6 bytes) + (16 * count)
    $dataOffset = 6 + (16 * $Images.Count)

    foreach ($img in $Images) {
        $rawSize = if ($img.Size -eq 256) { 0 } else { $img.Size }
        $s = [byte]$rawSize
        $bw.Write($s) # Width
        $bw.Write($s) # Height
        $bw.Write([byte]0) # Color count
        $bw.Write([byte]0) # Reserved
        $bw.Write([uint16]1) # Color planes
        $bw.Write([uint16]32) # Bit depth
        $bw.Write([uint32]$img.Bytes.Length) # Image data size
        $bw.Write([uint32]$dataOffset) # Image data offset
        $dataOffset += $img.Bytes.Length
    }

    # Image byte streams
    foreach ($img in $Images) {
        $bw.Write($img.Bytes)
    }

    $bw.Flush()
    $bw.Close()
    $fs.Close()
}

$icoDest1 = Join-Path $ProjectDir 'public\favicon.ico'
$icoDest2 = Join-Path $publicIconsDir 'site-gerencial.ico'

Save-IcoFile -Path $icoDest1 -Images $pngStreams
Save-IcoFile -Path $icoDest2 -Images $pngStreams

Write-Host "[SUCESSO] Icones gerados com sucesso:" -ForegroundColor Green
Write-Host "  - $icoDest1 ($((Get-Item $icoDest1).Length) bytes)"
Write-Host "  - $icoDest2 ($((Get-Item $icoDest2).Length) bytes)"
