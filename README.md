### INTRODUCTION:

This _native_api_amazon_ plugin is aimed at integrating the **Amazon Affiliate API** with a D8 site through the _affiliates_connect_amazon_ custom module, which would is being developed under the _affiliates_connect_ parent module. For the creation of this plugin the Drupal Rest resources would be used in the design process.

2 things would be essential in this process of developing native_api_amazon:

- Usage of the Amazon custom module Config Settings Form which would be used to retrieve the **Access Key ID** and the **Secret Key**.
- Once the above two keys are retrieved, Rest resources need to be created for the API endpoints according to the **Amazon Affiliate API** documentation. Some of them would be enabled by default which can be used as a block which a user can place anywhere and thereby display the selected products.

This repo contains the _affiliates_connect_amazon_ contributed module within which the proposed native API plugin would be developed.
