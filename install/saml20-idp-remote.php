<?php

// Dynamically build a metadata hash based on the contents of the
// sso_metadata directory

$metadataDir = '/data/aspen-discovery/sso_metadata/*';
$metadata = array();

foreach(glob($metadataDir) as $file) {
    // The below was shamelessly ripped from SimpleSAML's metadata-converter.php
    $xmldata = trim(file_get_contents($file));
    \SimpleSAML\Utils\XML::checkSAMLMessage($xmldata, 'saml-meta');
    $entities = SimpleSAML_Metadata_SAMLParser::parseDescriptorsString($xmldata);
    // get all metadata for the entities
    foreach ($entities as &$entity) {
        $entity = array(
            'shib13-sp-remote'  => $entity->getMetadata1xSP(),
            'shib13-idp-remote' => $entity->getMetadata1xIdP(),
            'saml20-sp-remote'  => $entity->getMetadata20SP(),
            'saml20-idp-remote' => $entity->getMetadata20IdP(),
        );
    }

    // transpose from $entities[entityid][type] to $output[type][entityid]
    $output = SimpleSAML\Utils\Arrays::transpose($entities);

    // merge all metadata of each type to a single string which should be added to the corresponding file
    foreach ($output as $type => &$entities) {
        foreach ($entities as $entityId => $entityMetadata) {
            if ($entityMetadata === null) {
                continue;
            }

            // remove the entityDescriptor element because it is unused, and only makes the output harder to read
            unset($entityMetadata['entityDescriptor']);
            $metadata[$entityId] = $entityMetadata;
        }
    }    
}
