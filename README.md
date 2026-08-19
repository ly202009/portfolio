# Basic Information

![Website Preview](https://raw.githubusercontent.com/ly202009/portfolio/079b6603e5388227443f7cec65cf2ebbe600684c/assets/portfolio.gif)

Live Demo [Here](liwenyao.ca)

To run a version locally:
- Fork the repository in github
- Clone a local copy onto your machine
- Have a working copy of PHP (8.5.0+) (Installation instructions [here](https://www.php.net/manual/en/install.php))
- In terminal, from the root directory of the local repository, run:
```bash
php -S localhost:8080
```



If you want the contact form to work, since I'm obviously not going to upload a .env file with my own login details, you have to add the .env file manually in the root folder. Inside the file, list two variables:
```
SMTP_USERNAME=yourusername@example.com
SMTP_PASSWORD=yoursmtppassword
```
And set the variables to your own SMTP account details. SMTP isn't too difficult to set up, there are plenty of tutorials online and gmail offers a free smtp service.
