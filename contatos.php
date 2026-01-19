<?php include_once("cabecalho.php"); ?>

<!-- Breadcrumbs -->
<section class="breadcrumbs-custom-inset">
  <div class="breadcrumbs-custom context-dark bg-overlay-39">
    <div class="container">
      <h2 class="breadcrumbs-custom-title">Fale Conosco</h2>
      <ul class="breadcrumbs-custom-path">
        <li><a href="index.php">Início</a></li>
        <li class="active">Contato</li>
      </ul>
    </div>
    <div class="box-position" style="background-image: url(images/25.p);"></div>
  </div>
</section>

<!-- Informações de Contato -->
<section class="section section-md section-first bg-default">
  <div class="container">
    <div class="row row-30 justify-content-center">

      <!-- WhatsApp -->
      <div class="col-sm-8 col-md-6 col-lg-4">
        <article class="box-contacts">
          <div class="box-contacts-body">
            <div class="box-contacts-icon fl-bigmug-line-cellphone55"></div>
            <div class="box-contacts-decor"></div>
            <p class="box-contacts-link">
              <a href="https://api.whatsapp.com/send?phone=5548999692743" target="_blank">
                +55 (48) 99969-2743
              </a>
            </p>
          </div>
        </article>
      </div>

      <!-- Endereço -->
      <div class="col-sm-8 col-md-6 col-lg-4">
        <article class="box-contacts">
          <div class="box-contacts-body">
            <div class="box-contacts-icon fl-bigmug-line-up104"></div>
            <div class="box-contacts-decor"></div>
            <p class="box-contacts-link">
              Rua João Born, 1244 – Ponte do Imaruim<br>
              Palhoça – SC
            </p>
          </div>
        </article>
      </div>

      <!-- Email -->
      <div class="col-sm-8 col-md-6 col-lg-4">
        <article class="box-contacts">
          <div class="box-contacts-body">
            <div class="box-contacts-icon fl-bigmug-line-chat55"></div>
            <div class="box-contacts-decor"></div>
            <p class="box-contacts-link">
              <a href="mailto:academiacamph@gmail.com">academiacamph@gmail.com</a>
            </p>
          </div>
        </article>
      </div>

    </div>
  </div>
</section>

<!-- Mapa e Formulário -->
<section class="section section-md section-last bg-default text-md-left">
  <div class="container">
    <div class="row row-50">

      <!-- Mapa -->
      <div class="col-lg-6 section-map-small">
        <iframe
          src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3534.513647106731!2d-48.65701535973799!3d-27.63957572406543"
          width="100%" height="450" style="border:0;" allowfullscreen loading="lazy">
        </iframe>
      </div>

      <!-- Formulário -->
      <div class="col-lg-6">
        <h4 class="text-spacing-50">Formulário de Contato</h4>

        <form class="rd-form rd-mailform" method="post" action="enviar.php"
          data-form-output="form-output-global" data-form-type="contact">

          <div class="row row-14 gutters-14">

            <div class="col-sm-6">
              <div class="form-wrap">
                <input class="form-input" id="contact-name" type="text" name="name"
                  data-constraints="@Required">
                <label class="form-label" for="contact-name">Nome</label>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="form-wrap">
                <input class="form-input" id="telefone" type="text" name="telefone"
                  data-constraints="@Required">
                <label class="form-label" for="telefone">Telefone / WhatsApp</label>
              </div>
            </div>

            <div class="col-12">
              <div class="form-wrap">
                <input class="form-input" id="contact-email" type="email" name="email"
                  data-constraints="@Email @Required">
                <label class="form-label" for="contact-email">E-mail</label>
              </div>
            </div>

            <div class="col-12">
              <div class="form-wrap">
                <label class="form-label" for="contact-message">Mensagem</label>
                <textarea class="form-input" id="contact-message" name="message"
                  data-constraints="@Required"></textarea>
              </div>
            </div>

          </div>

          <button class="button button-primary button-pipaluk" type="submit">
            Enviar Mensagem
          </button>

        </form>
      </div>

    </div>
  </div>
</section>

<?php include_once("rodape.php"); ?>

<div class="snackbars" id="form-output-global"></div>

<script src="js/core.min.js"></script>
<script src="js/script.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.11/jquery.mask.min.js"></script>
<script src="js/mascaras.js"></script>