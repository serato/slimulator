<?php
require('./vendor/autoload.php');

use Aws\S3\S3Client;

const IGNORE_VERSIONS = ['master', 'develop'];

$key                = getenv('ARTIFACTS_KEY');
$secret             = getenv('ARTIFACTS_SECRET');
$docsBucket         = getenv('ARTIFACTS_BUCKET');
$projectName        = getenv('TRAVIS_REPO_SLUG');
$objectPrefix       = 'serato/' . $projectName;

$versions = [];

$s3 = new S3Client([
    'version'     => 'latest',
    'region'      => 'us-east-1',
    'credentials' => [
        'key'    => $key,
        'secret' => $secret,
    ]
]);

$result = $s3->listObjects([
    'Bucket' => $docsBucket,
    'Prefix' => $objectPrefix
]);

if (isset($result['Contents'])) {
    foreach ($result['Contents'] as $object) {
        $version = substr(
            str_replace($objectPrefix . '/', '', $object['Key']),
            0,
            strpos(str_replace($objectPrefix . '/', '', $object['Key']), '/')
        );
        if (!in_array($version, IGNORE_VERSIONS) && !in_array($version, $versions)) {
            $versions[] = $version;
        }
    }
}

if (count($versions) > 0) {
    arsort($versions);
    $result = $s3->putObject([
        'Bucket'    => $docsBucket,
        'Key'       => 'js/' . $projectName . '.json',
        'Body'      => json_encode($versions)
    ]);
    echo "Versioned documentation links generateed for $docsBucket/$objectPrefix " .
         "for the following versions: " . json_encode($versions) . "\n";
    return true;
}

echo "No project versions found at s3://$docsBucket/$objectPrefix\n";
return false;
