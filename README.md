# Neo4jPHP library for CodeIgniter

### Instructions

1. Copy neo.php file to **application/libraries** folder in your project directory.
2. Create a folder "lib" in **application/libraries** folder and copy your Neo4jPHP folder in the **lib** folder.

### Usage 
* call neo4j library like this: `$this->load->library('neo');`

### Set up the client
* open the neo library file and set the client in constructor

`$this->client = new Client('server-url', server-port);`  
`$this->client->getTransport()->setAuth('user', 'password');`

### Execute cypher
`$cypher = "match(n) return n limit 25";`  
`$this->neo->execute_query($cypher);`
