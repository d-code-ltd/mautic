# Mautic Plugin for Rabbitmq

For exact workflow of RabbitMQ visit:
	- https://www.rabbitmq.com/
	
## Environment Variables: 
* RABBITMQ_SSL_CACERT_FILE - Location of SSL cacert.pem
* RABBITMQ_SSL_CERT_FILE - Location of SSL cert.pem
* RABBITMQ_SSL_KEY_FILE - Location of SSL key.pem

RabbitMQ host and credentials are defined in Plugin section of mautic. 

## Consumer:
* Consumer receives and processes messages for contacts, geofences and news
* Messages received are formated from standard to mautic readable format.
* Geofences are stored as segments.
* News DOTO;

## Producer:
* Producer is in use as event listener and listens only for changes in contact.

## SH:
* To start Mautic consumer use start_mautic_consumer.sh
* To stop Mautic consumer use stop_mautic_consumer.sh

## Entity message data formats

### Contact:
The value of the "data" field in the common message format for contact entities should be a JSON object with the following properties:

```
"email" - string, required, validated as email address
"first_name" - string, optional
"last_name" - string, optional
"birthday" - string, optional, validated as ISO 8601 formatted datetime
"gender" - string, optional, validated, possible values: "male", "female"
"address" - object, optional
	"country" - string, optional
	"country_code" - string, optional
	"state" - string, optional
	"state_code" - string, optional
	"county" - string, optional
	"city" - string, optional
	"zip_code" - string, optional
	"address_line1" - string, optional
	"address_line2" - string, optional
"mobile" - string, optional
"google_id" - string, optional
"facebook_id" - string, optional
"twitter_id" - string, optional
"linkedin_id" - string, optional
"points" - integer, optional (lead points or score)
"stage" - string, optional (lead stage)
"in_fence" - list of strings, optional (marks that in which geofences the contact currently resides in)
```

### GeoFence:
The value of the "data" field in the common message format for geofence entities should be a JSON object with the following properties:
```
"id" - string, required
"name" - string, required
"multi_polygon" - object, GeoJSON MultiPolygon, required
```
### News: TODO