# PDF Generation Issues

Small project using Laravel & Docker to demonstrate a couple issues with PDF generation.

1. [Basics](basics)
2. [Docker Setup](#docker-setup)
3. [puppeteer Issue](#puppeteer-issue)
4. [paged.js Issue](#paged.js-issue)

## Basics

There are two main test files in the `site/resources/views` folder that controls the PDF: `html.blade.php` & `paged_js.blade.php`
They contain the raw HTML for generating the PDF and any specific JavaScript for the paged.js library to create pages.

If you prefer to test certain aspects outside Docker, there is a `site/public/demo.html` file that can be loaded directly.
However the puppeteer issue only happens when using Docker for some reason. Paged.js issue happens regardless of environment.

After setting up docker project below:

1. http://demo.test/pdf - 
use this URL to generate a PDF using the demo HTML page
2. http://demo.test/html - 
Use this URL to view the content rendered to PDF as a HTML page. 

## Docker Setup

1. Build an image using the included Dockerfile
````
# the site/storage folder needs write for PDF output. Build with your current UID
$ docker build --build-arg UID=$(id -u) -t pdf_test .
````

2. Run the docker-compose.yml file
````
$ docker-compose up -d
````

## puppeteer Issue

### ISSUE: Internal Anchor Tag Links - Wrong Location

The anchor tag links used in a Table of Contents link to certain sections in the PDF (built by paged.js). 
In the rendered HTML it works fine but since I've updated the Node version to 20.X in the `Dockerfile`, the TOC
links that are generated do not go to the correct location. (Rolling back to the old Node doesn't work either so 
I am wondering what dependent package was updated that handles creating these links)

I opened the PDF with a text editor and found references to my HTML #ids for each anchor tag. The last number in each
line is negative. If I change the number from negative to positive, clicking the links at least takes you to the correct
page.

````
48 0 obj
<</section1 [11 0 R /XYZ 72 -684 0]
/section2 [42 0 R /XYZ 72 -10128.75 0]>>
endobj
````

Hoping this helps track down why this happens and what section of puppeteer handles internal link as I cannot figure 
out the difference between local dev & docker image. 

**NOTE** This only happens in a docker build. If I run the exact same code on a local dev web server without Docker,
the document links work as expected. If I just use Chrome and print to PDF manually, the links work as well.


## paged.js Issue

### ISSUE (current WIP beta build)

1. Repeating table headers no longer continue on the following pages

With the current beta build, The RepeatingTableHeaders PageHandler no longer adds the table headers to all pages
when the table 