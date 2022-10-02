# LoopIN App
This project “LOOP IN Analytics System” is used to
gather the responses and analyse the responses of the customers
on various products and services and the surveys are created. The project is
designed HTML-REACT JS,CSS,JAVASCRIPT as front end and NODE JS EXPRESS JS MONGO DB as
backend which works in any browsers. The coding language used
HTML and JAVASCRIPT. LOOP IN ANALYTICS SYSTEM is used to
gather and analyse the responses of the users on a particular company's
products and services which can be used to improve these aspects and
it is done in a single website. The authenticated users can login and
create survey questions and can respond to it only once because their
responses should be unambigous and unique which will be used to analyse
various company's products and services. The users can see the results of
these surveys in the form of a piechart. It is an easiest platform for all
users to analyse about their company's products and services.

 
## User Stories

* As an authenticated user, I can keep my polls and come back later to access them.
* As an authenticated user, I can share my polls with my friends.
* As an authenticated user, I can see the aggregate results of my polls.
* As an authenticated user, I can delete polls that I decide I don't want anymore.
* As an authenticated user, I can create a poll with any number of possible items.
* As an unauthenticated or authenticated user, I can see and vote on everyone's polls.
* As an unauthenticated or authenticated user, I can see the results of polls in chart form. (This could be implemented using Chart.js or Google Charts.)
* As an authenticated user, if I don't like the options on a poll, I can create a newoption.

## Getting Started

Include a `.env` file in the `server` directory with the following environment variables.

```
PORT = 4000
DATABASE = 'mongodb://localhost/<DATABASE_NAME>'
SECRET = 'ThisIsATemporarySecretKey'
```
These dependencies can be installed using the npm i "dependency-name".

## Built with

* nodejs
* express
* mongodb
* mongoose
* bcrypt
* jsonwebtoken
* react
