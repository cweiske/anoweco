<?php
namespace anoweco;

class Storage
{
    public function __construct()
    {
        require __DIR__ . '/../../data/config.php';
        $this->db = new \PDO($dbdsn, $dbuser, $dbpass);
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Store a new comment into the database
     *
     * @param object  $json   Micropub create JSON
     * @param integer $userId ID of the user whom this comment belongs
     *
     * @return integer Comment ID
     * @throws \Exception
     */
    public function addComment($json, $userId)
    {
        $stmt = $this->db->prepare(
            'INSERT INTO comments SET'
            . '  comment_user_id = :userId'
            . ', comment_published = NOW()'
            . ', comment_of_url = :ofUrl'
            . ', comment_type = :type'
            . ', comment_json = :json'
        );

        $ofUrl = '';
        if (isset($json->properties->{'in-reply-to'})) {
            $ofUrl = reset($json->properties->{'in-reply-to'});
        }
        $stmt->execute(
            array(
                ':userId' => $userId,
                ':ofUrl'  => $ofUrl,
                ':type'   => reset($json->type),
                ':json'   => json_encode($json),
            )
        );
        return $this->db->lastInsertId();
    }

    /**
     * @return null|object NULL if not found, comment object otherwise
     */
    public function getComment($id)
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM comments WHERE comment_id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetchObject();

        if ($row === false) {
            return null;
        }

        $json = json_decode($row->comment_json);
        $json->Xrow = $row;
        //FIXME: load user

        $stmt = $this->db->prepare(
            'SELECT * FROM users WHERE user_id = ?'
        );
        $stmt->execute([$row->comment_user_id]);
        $rowUser = $stmt->fetchObject();
        if ($rowUser === false) {
            $rowUser = (object) array(
                'user_id'   => 0,
                'user_name' => 'Anonymous',
                'user_imageurl' => '',
            );
        }

        $json->user = $rowUser;
        return $json;
    }

    /**
     * @return null|object NULL if not found, user database row otherwise
     */
    public function getUser($id)
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM users WHERE user_id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetchObject();

        if ($row === false) {
            return null;
        }
        return $row;
    }

    public function findUser($name, $imageurl)
    {
        $stmt = $this->db->prepare(
            'SELECT user_id FROM users'
            . ' WHERE user_name = ? AND user_imageurl = ?'
        );
        $stmt->execute([$name, $imageurl]);
        $row = $stmt->fetchObject();

        if ($row === false) {
            return null;
        }
        return $row->user_id;
    }

    public function createUser($name, $imageurl)
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users SET'
            . '  user_name = :name'
            . ', user_imageurl = :imageurl'
        );
        $stmt->execute(
            array(
                ':name'     => $name,
                ':imageurl' => $imageurl,
            )
        );
        return $this->db->lastInsertId();
    }
}
?>
