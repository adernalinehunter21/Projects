# LoopIN App

 The LoopIn App created with the MERN stack. The application is a survey website which can be used to publish huge amounts of survey questions which will allow the organizations to tap into the sentiments of the users.The results of the survey questions are displayed in the form of a PieChart.

 
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
