##### An API project to obtain data from your processes on the Pipefy platform.

## The problem

Pipefy is a very useful platform for project management and workflow automation, and as such, it can provide very useful information on each process' performance. A process (denominated pipe in the platform) is defined by cards, that host a variety of fields, and phases, in which cards can move between. After hosting and defining such processes in the platform, obtaining their data in a fast and automated way is a really big step towards building real-time reports for insight generation among the business areas, which can then be done with Data Visualization tools such as Powerbi, Tableau or Metabase.

This API retrieves data using their Graph's query language, applies processing to better prepare data for correct database insertion and finally checks if there is an existing entry in the database, so that it either updates existing data or inserts new data.
It also builds a phase history log with each phase's first entrance date, so that later down the data pipeline other apps can analyze each process' metrics with both phase and time dimensions.

## References and Tools Used

This API was built using PHP and the Laragon toolkit. The sample calls can be easily tried on their API explorer, after logging in to your Pipefy account, which is linked down. 

The documentation for the Facebook's Graph API can be found here:
1. [Pipefy GraphQL API](https://developers.pipefy.com/reference/what-is-graphql)
2. [API References](https://api-docs.pipefy.com/reference/overview/Card/)
3. [Pipefy GraphQL Explorer](https://app.pipefy.com/graphiql)
