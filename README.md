# Neo4jPHP library for CodeIgniter

### Instructions

1. Copy the repository files to **application/libraries** folder in your project directory.

### Usage 
* call neo4j library like this: `$this->load->library('neo');`

### Set up the client
* open the neo library file and set the client in constructor

`$this->client = new Client('server-url', server-port);`  
`$this->client->getTransport()->setAuth('user', 'password');`

### Execute cypher
`$cypher = "match(n) return n limit 25";`  
`$this->neo->execute_query($cypher);`

###Read [Wiki](https://github.com/asfandahmed/Neo4jPHP-lib-for-CodeIgniter/wiki) for class reference 
