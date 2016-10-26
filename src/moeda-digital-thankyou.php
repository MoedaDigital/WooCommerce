<?php
/**
 * Thankyou page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/thankyou.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     2.2.0
 */

$url = 'https://moeda.digital/Modulos/ItauBoleto/Boleto.aspx?Pedido=586c784a595542357330593d';

$param = $_GET['param'];

$urlBoleto = base64_decode($param);

$p = split('\|', $urlBoleto);

$order_id = $p[0];

?>
<html>
<body>

    <h1>
        Pedido efetuado com sucesso.
    </h1>

    <ul class="woocommerce-thankyou-order-details order_details">
        <li class="order">
            Pedido Número:
            <strong>
                <?php echo $order_id  ?>
            </strong>
        </li>
        <li class="order">
            Status:
            <strong>
                <?php echo $p[1]  ?>
            </strong>
        </li>
    </ul>
    <div class="clear"></div>

    <?php
    if ( $p[1] = 'PENDENTE') { ?>

    <br />Voc&ecirc; ser&aacute; redirecionado para o ambiente seguro da sua Institui&ccedil;&atilde;o Financeira em alguns instantes atrav&eacute;s da
    <b>Moeda.Digital</b>- Meios de pagamento para com&eacute;rcio eletr&ocirc;nico.

    <br />Se isso n&atilde;o ocorrer, por favor clique imagem abaixo.
    <br />

    <form action='<?php echo  $p[2] ; ?>' method='POST' name='formItauBoleto' target='ItauBoleto'>
        <input type='image' src='<?php echo  $p[3] ; ?>images/boleto.png' onclick='document.formItauBoleto.submit()' alt='ItauBoleto' />
    </form>

    <script language='Javascript'>
        document.formItauBoleto.submit();
    </script>

    <?php } ?>
</body>
</html>