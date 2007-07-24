<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.phpdoctrine.com>.
 */

/**
 * Doctrine_Search_Indexer
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision$
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 */
class Doctrine_Search_Indexer
{
    public function indexDirectory($dir)
    {
    	if ( ! file_exists($dir)) {
    	   throw new Doctrine_Search_Indexer_Exception('Unknown directory ' . $dir);
    	}

        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir), RecursiveIteratorIterator::LEAVES_ONLY);

        $q = new Doctrine_Query();
        $q->delete()
          ->from('Doctrine_Search_File f')
          ->where('f.url LIKE ?', array($dir . '%'))
          ->execute();

        // clear the index
        $q = new Doctrine_Query();
        $q->delete()
          ->from('Doctrine_Search_File_Index i')
          ->where('i.foreign_id = ?')
          ->execute();


        $conn = Doctrine_Manager::connection();

        $coll = new Doctrine_Collection('Doctrine_Search_File');

        foreach ($it as $file) {
            $coll[]->url = $file->getFilePath();
        }
        
        $coll->save();

        foreach ($coll as $record) {
            $this->updateIndex($record);                          	
        }
    }
    
    public function updateIndex(Doctrine_Record $record)
    {
    	$fields = array('url', 'content');

        $class = 'Doctrine_Search_File_Index';

        foreach ($fields as $field) {
            $data  = $record->get($field);

            $terms = $this->analyze($data);

            foreach ($terms as $pos => $term) {
                $index = new $class();

                $index->keyword = $term;
                $index->position = $pos;
                $index->field = $field;
                $index->$name = $record;
                
                $index->save();
            }
        }
    }
}
