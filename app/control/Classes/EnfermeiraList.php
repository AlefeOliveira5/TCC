<?php
/**
 * @author Alefe
 */
class EnfermeiraList extends TPage
{
    protected $form;
    protected $datagrid;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('list_enfermeira');
        $this->form->setFormTitle('Enfermeira');

        
        $opcao = new TCombo('opcao');
        $nome = new TEntry('nome_enfer');
        $crm2 = new TEntry('coren');
        $email = new TEntry('email');
        //$espec = new TEntry('espec_id');

        $items= array();
        $items['nome_enfer'] = 'Nome Enfermeira';
        $items['coren'] = 'COREN';
        $items['email'] = 'Email';
        //$items['espec_id'] = 'Especialidade';
        

        $opcao->addItems($items);
        $opcao->setValue('nome_enfermeira');
        $opcao->setValue('coren');
        $opcao->setValue('email');

        $opcao->setDefaultOption(FALSE);

        $nome->setSize('80%');
        $opcao->setSize('80%');

        $this->form->addFields( [new TLabel('Selecione o campo')], [$opcao]);
        $this->form->addFields( [new TLabel('Buscar')], [$nome]);

        $btn = $this->form->addAction('Buscar', new TAction(array($this, 'onSearch')), 'fa:search');
        $btn->class = 'btn btn-sm btn-primary';

        $this->form->addAction('Novo',  new TAction(array('EnfermeiraForm', 'onEdit')), 'fa:plus green');
        $this->form->addAction( 'Limpar Busca' , new TAction(array($this, 'onClear')), 'fa:eraser red');

        //DATAGRID ------------------------------------------------------------------------------------------

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->datatable = 'true';
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);

        // DATA GRID
        $nome_enfer = new TDataGridColumn('nome_enfer', 'Nome Enfermeira', 'left');
        $coren = new TDataGridColumn('coren', 'COREN', 'left');
        $email = new TDataGridColumn('email', 'Email', 'left');

        $this->datagrid->addColumn($nome_enfer);
        $this->datagrid->addColumn($coren);
        $this->datagrid->addColumn($email);
        

        // fim data grid
        $actionEdit = new TDataGridAction(array('EnfermeiraForm', 'onEdit'));
        $actionEdit->setLabel('Editar');
        $actionEdit->setImage( "far:edit blue" );
        $actionEdit->setField('id');
        $this->datagrid->addAction($actionEdit);

        $actionDelete = new TDataGridAction(array($this, 'onDelete'));
        $actionDelete->setLabel('Deletar');
        $actionDelete->setImage( "far:trash-alt red" );
        $actionDelete->setField('id');
        $this->datagrid->addAction($actionDelete);

        $this->datagrid->createModel();

        //FIM DATAGRID -----------------------------------------------------------------------------------------

        $container = new TVBox();
        $container->style = "width: 100%";
        $container->add( $this->form);
        $container->add( TPanelGroup::pack( NULL, $this->datagrid ) );

        $this->datagrid->disableDefaultClick();

        parent::add( $container );
    }

    public function onClear() {

        if (TSession::getValue('filter_')) {
            TSession::setValue('filter_', null);
        }

        $this->onReload();

    }

    public function onDelete( $param = NULL )
    {
        if( isset( $param[ "key" ] ) ) {

            $action_ok = new TAction( [ $this, "Delete" ] );
            $action_cancel = new TAction( [ $this, "onReload" ] );

            $action_ok->setParameter( "key", $param[ "key" ] );

            new TQuestion( "Deseja remover o registro?", $action_ok, $action_cancel,  "Deletar");

        }
    }

    function Delete( $param = NULL )
    {
        try {

            TTransaction::open('DB_GMU');//Nome Arquivo conexao Banco de dados

            $object = new Enfermeira ($param['key']); // SEU RECORD <

            $object->delete();

            TTransaction::close();

            $this->onReload();

            new TMessage( "info", "Registro deletado com sucesso!" );

        } catch ( Exception $ex ) {

            TTransaction::rollback();

            new TMessage( "error",  $ex->getMessage() .'.' );

        }

    }

    public function onReload( $param = NULL )
    {

        try {

            TTransaction::open('DB_GMU');

            $repository = new TRepository('Enfermeira');//RECORD

            $criteria = new TCriteria;
            $criteria->setProperty('order', 'nome_enfer');

            if (TSession::getValue('filter_')) {
                $filters = TSession::getValue('filter_');
                foreach ($filters as $filter) {
                    $criteria->add($filter);
                }
            }

            $objects = $repository->load( $criteria, FALSE );

            $this->datagrid->clear();

            if ( !empty( $objects ) ) {
                foreach ( $objects as $object ) { 
                    //$object->datanasc = TDate::date2br($object->datanasc);            
                    $this->datagrid->addItem( $object );
                }
            }

            $criteria->resetProperties();

            TTransaction::close();

        } catch ( Exception $ex ) {

            TTransaction::rollback();

            new TMessage( "error",  $ex->getMessage()  );

        }

    }

    public function onSearch()
    {

        $data = $this->form->getData();

        try {

            if( !empty( $data->opcao ) && !empty( $data->nome_enfer ) ) {

                $filter = [];

                switch ( $data->opcao ) {

                    default:
                        $filter[] = new TFilter( "LOWER(" . $data->opcao . ")", "LIKE", "NOESC:LOWER( '%" . $data->nome_enfer . "%' )" );
                        break;

                }

                TSession::setValue('filter_', $filter);

                $this->form->setData( $data );

                $this->onReload();

            } else {

                TSession::setValue('filter_', '');

                $this->onReload();

                $this->form->setData( $data );

                new TMessage( "error", "Selecione uma opção e informe os dados da busca corretamente!" );

            }

        } catch ( Exception $ex ) {

            TTransaction::rollback();

            $this->form->setData( $data );

            new TMessage( "error",  $ex->getMessage() .'.' );

        }

    }

    public function show()
    {

        $this->onReload();

        parent::show();

    }


}
?>