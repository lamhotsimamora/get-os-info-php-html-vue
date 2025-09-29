<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
$os = PHP_OS_FAMILY;

$data = [
    "os" => php_uname(),
    "cpu" => [
        "model" => "Unknown",
        "usage" => rand(10, 90) // simulasi CPU load
    ],
    "ram" => [
        "total" => 0,
        "used"  => 0
    ],
    "gpu" => [
        "model" => "Unknown",
        "usage" => rand(10, 90) // simulasi GPU load
    ],
    "disk" => [
        "type"  => "Unknown",
        "total" => 0,
        "used"  => 0
    ]
];

if ($os === "Windows") {
    // RAM
    $total = shell_exec("wmic computersystem get TotalPhysicalMemory /Value");
    preg_match("/TotalPhysicalMemory=(\d+)/", $total, $matches);
    if (isset($matches[1])) {
        $totalBytes = (int)$matches[1];
        $data["ram"]["total"] = round($totalBytes / (1024*1024*1024), 2);
    }

    $free = shell_exec("wmic OS get FreePhysicalMemory /Value");
    preg_match("/FreePhysicalMemory=(\d+)/", $free, $matches);
    if (isset($matches[1])) {
        $freeKB = (int)$matches[1];
        $freeGB = $freeKB / (1024*1024);
        $data["ram"]["used"] = round($data["ram"]["total"] - $freeGB, 2);
    }

    // CPU model
    $cpu = shell_exec("wmic cpu get Name /Value");
    preg_match("/Name=(.*)/", $cpu, $matches);
    if (isset($matches[1])) {
        $data["cpu"]["model"] = trim($matches[1]);
    }

    // Disk C:\
    $disk = shell_exec("wmic logicaldisk get size,freespace,caption");
    preg_match("/C:\s+(\d+)\s+(\d+)/", $disk, $matches);
    if (count($matches) >= 3) {
        $free = (int)$matches[1];
        $size = (int)$matches[2];
        $data["disk"]["total"] = round($size / (1024*1024*1024), 2);
        $data["disk"]["used"]  = round(($size - $free) / (1024*1024*1024), 2);
        $data["disk"]["type"]  = "HDD/SSD";
    }

    // GPU (sederhana)
    $gpu = shell_exec("wmic path win32_videocontroller get name /Value");
    preg_match("/Name=(.*)/", $gpu, $matches);
    if (isset($matches[1])) {
        $data["gpu"]["model"] = trim($matches[1]);
    }

} else { // Linux
    // RAM
    $meminfo = file_get_contents("/proc/meminfo");
    preg_match("/MemTotal:\s+(\d+)/", $meminfo, $matches);
    $totalKB = (int)$matches[1];
    preg_match("/MemAvailable:\s+(\d+)/", $meminfo, $matches);
    $availKB = (int)$matches[1];
    $data["ram"]["total"] = round($totalKB / 1024 / 1024, 2);
    $data["ram"]["used"]  = round(($totalKB - $availKB) / 1024 / 1024, 2);

    // CPU
    $cpuinfo = file_get_contents("/proc/cpuinfo");
    preg_match("/model name\s+:\s+(.+)/", $cpuinfo, $matches);
    if (isset($matches[1])) {
        $data["cpu"]["model"] = trim($matches[1]);
    }

    // Disk root /
    $diskTotal = disk_total_space("/");
    $diskFree  = disk_free_space("/");
    $data["disk"]["total"] = round($diskTotal / (1024*1024*1024), 2);
    $data["disk"]["used"]  = round(($diskTotal - $diskFree) / (1024*1024*1024), 2);
    $data["disk"]["type"]  = "SSD/HDD";

    // GPU (dummy karena sulit dari PHP)
    $data["gpu"]["model"] = "Generic GPU";
}

echo json_encode($data, JSON_PRETTY_PRINT);
