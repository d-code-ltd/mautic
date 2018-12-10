
GDPR Compliancy
===================

## GDPR compatible unsubscribe
Since the core Mautic doesn't support any proper soultion that safisfies the GDPR regulations, some addtitional code modifications were required as seen below. The most important thing is, after a contact unsubscribes (gets the do-not-contact flag), we cannot store any personal data of him/her anymore. However for Hungarian law regulations we have to keep some data for possible future investigations.

### Email hash custom field
For proper working, there must be a specific custom field, called email hash (**Alias: email_hash**) which will contain the salted md5 hash value of the original e-mail address of the contact.
Relevant code:
app/bundles/LeadBundle/Entity/Lead.php::gdprUnsubscribe()
app/bundles/LeadBundle/Model/LeadModel.php::addDncForLead()

### Delete, hash, keep contact data
On the Mautic UI Custom fields page, administrators can define what should happen to the contacts' data after flaggint hium/her with as Do-not-contact. This way when this event occours the fields' data will change accordingly to the previous settings. The default mauti custom fields' aliases can't be changed by the user, these should be changed in the database directly as needed.

 - **Delete** the field's data: add 'removable' (without apostrophes) to anywhere in the custom field's Alias
 - **Hash** the field's data: add 'hashable' (without apostrophes) to anywhere in the custom field's Alias
 - **Keep** the field's data: no additional steps needed
Relevant code:
app/bundles/LeadBundle/Entity/Lead.php::handeFieldsOnUnsubscribe()

### Searching hashed email values
When looking for a Do-not-contact contact, who now only has the hashed e-mail address, on the Mautic UI Contacts page simply type the following to the search bar:

    email_hash: email_you_are@looking.for
After the now anonymus contacts should be shown who had the typed e-mail address once in the past.
Relevant code:
app/bundles/LeadBundle/Controller/LeadController.php::indexAction()

### Lose tracking of Do-not-contact contact
After we've made a contact Do-not-contact, removed the personal data, hashed the e-mail address, we shouldn't identify the same user ever again. However the user might still have the Cookie that identifies with the Do-not-contact contact. In this case, when the user tries to use this cookie, the code checks if it belongs to a Do-not-contact contact, it woould handle the user as a newcomer instead and creates a new contact with new Cookie.
Relevant code:
app/bundles/LeadBundle/Tracker/Service/ContactTrackingService/ContactTrackingService.php::getTrackedLead()
