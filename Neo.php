<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
use Everyman\Neo4j\Client,
	Everyman\Neo4j\Index\NodeIndex,
	Everyman\Neo4j\Relationship,
	Everyman\Neo4j\Node,
    Everyman\Neo4j\Traversal,
	Everyman\Neo4j\Cypher,
    Everyman\Neo4j\Cypher\Query;

error_reporting(-1);
ini_set('display_errors', 1);
spl_autoload_register(function ($sClass) {
	$sLibPath = __DIR__.'/lib/';
	$sClassFile = str_replace('\\',DIRECTORY_SEPARATOR,$sClass).'.php';
	$sClassPath = $sLibPath.$sClassFile;
	if (file_exists($sClassPath)) {
		require($sClassPath);
	}
});

class Neo {

	protected $client;
    private $local=true;
	public function __construct()
	{
        if($this->local){
            $this->client = new Client();
            $this->client->getTransport()
            ->setAuth('neo4j', 'bookrack');    
        }else{
            $this->client = new Client('bookrack.sb02.stations.graphenedb.com', 24789);
            $this->client->getTransport()
            ->setAuth('bookrack', 'sgd991UcxP2tVd3zzOkc');
        }
		
        
	}
    public function add_index($name)
    {	
		return $name = new NodeIndex($this->client, $name);
    }
    public function add_to_index($index,$node,$property)
    {
    	return $index->add($node, $property, $node->getProperty($property));
    }
    public function get_node($id)
    {
        return $this->client->getNode($id);
    }
    public function add_node($node,$property,$value)
    {
    	$node = $this->client->makeNode()->setProperty($property, $value)->save();
    }
    public function remove_node($id)
    {
        $node = $this->client->getNode($id);
        $node->delete();
    }
    public function add_relation($nodeId1, $nodeId2, $name, $data=array())
    {
        try{
            $node1=$this->client->getNode($nodeId1); // get first node
            $node2=$this->client->getNode($nodeId2); // get second node
            /* node1 relates to node2*/
            $relation = $this->client->makeRelationship();
            $relation->setStartNode($node1);
            $relation->setEndNode($node2);
            $relation->setType($name);
            if(!empty($data)){
                foreach ($data as $key => $value) {
                if($value != NULL)
                    $relation->setProperty($key,$value)->save();
                }    
            }
            else
                $relation->save();
            return $relation->getId();
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }
        
    }
    public function get_relations($nodeId)
    {
        $node=$this->client->getNode($nodeId);
        return $node->getRelationships();
    }
    public function delete_relation($id)
    {
        $relation = $this->client->getRelationship($id);
        return $relation->delete();
    }
    public function add_label($node_id,$name)
    {
        $node = $this->client->getNode($node_id);
    	$label = $this->client->makeLabel($name);
        $node->addLabels(array($label));
    }
    public function remove_label($node_id,$name)
    {
        $node = $this->client->getNode($node_id);
        $label = $this->client->makeLabel($name);
        $node->removeLabels(array($label));   
    }
    public function get_label_nodes($label)
    {
        $label = $this->client->makeLabel($label);
        return $nodes = $label->getNodes();
    }
    public function begin_transaction()
    {
        return $transaction = $this->client->beginTransaction();
    }
    public function add_query_transaction($qStr,$nodeId=array())
    {
        return $query = new Query($this->client,$qStr,$node_id);
    }
    public function commit_transaction($transaction)
    {
        $transaction->commit();
        if($transaction->isClosed())
            echo "Tranaction Closed:No more statements can be added!";
        if($transaction->isError())
            echo "Transaction Error:No more statements can be added!";
    }
    public function rollback_transaction($transaction)
    {
        $transaction->rollback();
        if($transaction->isClosed())
            echo "Tranaction Closed:No more statements can be added!";
        if($transaction->isError())
            echo "Transaction Error:No more statements can be added!";
    }
    public function keep_alive_transaction($transaction)
    {
        $transaction->keepAlive();
    }
    public function transaction($quries=array())
    {
        $transaction = $this->begin_transaction();
        try{
            foreach ($quries as $query)
                $this->add_query_transaction($query);
            $this->commit_transaction($transaction);
        }
        catch(Exception $e)
        {
            $this->rollback_transaction($transaction);
            echo $e->getMessage();
        }

    }
    public function execute_query($query_str,$parameters=array())
    {
        try{
            $query = new Query($this->client,$query_str,$parameters);
            //print_r($query);
            return $query->getResultSet();    
        }
        catch(Exception $e){
            echo $e->getMessage();
        } 
    }
    public function traverse($relation,$depth=1)
    {
        $traversal = new Traversal($this->client);
        $traversal->addRelationship($relation,Relationship::DirectionOut)
                    ->setPruneEvaluator(Traversal::PruneNone)
                    ->setReturnFilter(Traversal::ReturnAll)
                    ->setMaxDepth($depth);
        return $nodes = $traversal->getResults($startNode, Traversal::ReturnTypeNode);
    }
    public function create_book($property,$value)
    {
        try
        {
            /* creating node index */
            $books = new NodeIndex($this->client,'Book');
           /* creating label*/
            $label = $this->client->makeLabel('Book');
            /* creating node */
            $node = $this->client->makeNode()->setProperty($property,$value)->save();
            /* labeling node */
            $node->addLabels(array($label));
            /* adding node to index */
            $books->add($node, $property, $node->getProperty($property));
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }
    }
    public function insert($modelName,$data)
    {
        try
        {
           /* creating label*/
            $label = $this->client->makeLabel($modelName);
            /* creating node */
            $node = $this->client->makeNode();
            /* setting properties */
            foreach ($data as $key => $value) {
                if($value != "")
                    $node->setProperty($key,$value)->save();    
            }
            /* labeling node */
            $node->addLabels(array($label));
            return $node->getId();
        }
        catch(Exception $e)
        {
            echo $e->getMessage();   
        }
    }
    public function update($id,$data){
        try{
            $node = $this->client->getNode($id);
            foreach ($data as $key => $value) {
                if($value != "")
                    $node->setProperty($key,$value)->save();    
            }
            return $node->getId();
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }
    }
    public function insert_with_index($modelName,$data)
    {
        try
        {
            /* creating node index */
            $nodeIndex = new NodeIndex($this->client,$modelName);
           /* creating label*/
            $label = $this->client->makeLabel($modelName);
            /* creating node */
            $node = $this->client->makeNode()->setProperty($property,$value)->save();
            /* labeling node */
            $node->addLabels(array($label));
            /* adding node to index */
            $nodeIndex->add($node, $property, $node->getProperty($property));
        }
        catch(Exception $e)
        {
            echo $e->getMessage();   
        }
    }
}

/* End of file Neo.php */