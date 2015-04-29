export C2M_USERNAME=MyUserName
export C2M_PASSWORD=MyPassword
export C2M_URL=https://dev-rest.click2mail.com/molpro/
export C2M_DOCUMENT=Letter-Separate_Address_10_Envelope.docx
#Url for submitting real order to production.
#export C2M_URL=https://rest.click2mail.com/molpro/
phpunit RestApiClientTest.php
