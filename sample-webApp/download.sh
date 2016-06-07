#!/bin/bash

wget https://www.irs.gov/pub/fatca/FATCAXMLSchemav1.zip
unzip FATCAXMLSchemav1.zip
rm FATCAXMLSchemav1.zip

wget https://www.irs.gov/pub/fatca/SenderMetadatav1.1.zip
unzip SenderMetadatav1.1.zip 
rm SenderMetadatav1.1.zip 

wget https://ides-support.com/Downloads/encryption-service_services_irs_gov.crt
