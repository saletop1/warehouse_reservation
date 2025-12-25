<!-- Custom Loader Modal -->
<div class="modal fade" id="customLoadingModal" tabindex="-1" aria-labelledby="customLoadingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 bg-transparent">
            <div class="modal-body p-0">
                <!-- Loader from Uiverse.io by DevPTG - Modified -->
                <div class="loader-stock" id="loaderStock">
                    <div>S</div>
                    <div>T</div>
                    <div>O</div>
                    <div>C</div>
                    <div>K</div>
                    <div>!</div>
                </div>
                <div class="text-center text-white mt-4" id="customLoadingText">Checking Stock...</div>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom Loader from Uiverse.io by DevPTG - Modified */
.loader-stock {
  position: relative;
  width: 600px;
  height: 36px;
  left: 50%;
  top: 40%;
  margin-left: -300px;
  overflow: visible;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
  cursor: default;
}

.loader-stock div {
  position: absolute;
  width: 20px;
  height: 36px;
  opacity: 0;
  font-family: Helvetica, Arial, sans-serif;
  animation: move 2s linear infinite;
  -o-animation: move 2s linear infinite;
  -moz-animation: move 2s linear infinite;
  -webkit-animation: move 2s linear infinite;
  transform: rotate(180deg);
  -o-transform: rotate(180deg);
  -moz-transform: rotate(180deg);
  -webkit-transform: rotate(180deg);
  font-size: 28px;
  font-weight: bold;
}

/* Warna default untuk checking stock */
.loader-stock.checking-stock div {
  color: #35C4F0;
}

/* Warna untuk processing transfer */
.loader-stock.processing-transfer div {
  color: #4CAF50;
}

/* Warna untuk resetting */
.loader-stock.resetting div {
  color: #FF9800;
}

.loader-stock div:nth-child(2) {
  animation-delay: 0.2s;
  -o-animation-delay: 0.2s;
  -moz-animation-delay: 0.2s;
  -webkit-animation-delay: 0.2s;
}

.loader-stock div:nth-child(3) {
  animation-delay: 0.4s;
  -o-animation-delay: 0.4s;
  -webkit-animation-delay: 0.4s;
  -webkit-animation-delay: 0.4s;
}

.loader-stock div:nth-child(4) {
  animation-delay: 0.6s;
  -o-animation-delay: 0.6s;
  -moz-animation-delay: 0.6s;
  -webkit-animation-delay: 0.6s;
}

.loader-stock div:nth-child(5) {
  animation-delay: 0.8s;
  -o-animation-delay: 0.8s;
  -moz-animation-delay: 0.8s;
  -webkit-animation-delay: 0.8s;
}

.loader-stock div:nth-child(6) {
  animation-delay: 1s;
  -o-animation-delay: 1s;
  -moz-animation-delay: 1s;
  -webkit-animation-delay: 1s;
}

@keyframes move {
  0% {
    left: 0;
    opacity: 0;
  }

  35% {
    left: 41%;
    -moz-transform: rotate(0deg);
    -webkit-transform: rotate(0deg);
    -o-transform: rotate(0deg);
    transform: rotate(0deg);
    opacity: 1;
  }

  65% {
    left: 59%;
    -moz-transform: rotate(0deg);
    -webkit-transform: rotate(0deg);
    -o-transform: rotate(0deg);
    transform: rotate(0deg);
    opacity: 1;
  }

  100% {
    left: 100%;
    -moz-transform: rotate(-180deg);
    -webkit-transform: rotate(-180deg);
    -o-transform: rotate(-180deg);
    transform: rotate(-180deg);
    opacity: 0;
  }
}

@-moz-keyframes move {
  0% {
    left: 0;
    opacity: 0;
  }

  35% {
    left: 41%;
    -moz-transform: rotate(0deg);
    transform: rotate(0deg);
    opacity: 1;
  }

  65% {
    left: 59%;
    -moz-transform: rotate(0deg);
    transform: rotate(0deg);
    opacity: 1;
  }

  100% {
    left: 100%;
    -moz-transform: rotate(-180deg);
    transform: rotate(-180deg);
    opacity: 0;
  }
}

@-webkit-keyframes move {
  0% {
    left: 0;
    opacity: 0;
  }

  35% {
    left: 41%;
    -webkit-transform: rotate(0deg);
    transform: rotate(0deg);
    opacity: 1;
  }

  65% {
    left: 59%;
    -webkit-transform: rotate(0deg);
    transform: rotate(0deg);
    opacity: 1;
  }

  100% {
    left: 100%;
    -webkit-transform: rotate(-180deg);
    transform: rotate(-180deg);
    opacity: 0;
  }
}

@-o-keyframes move {
  0% {
    left: 0;
    opacity: 0;
  }

  35% {
    left: 41%;
    -o-transform: rotate(0deg);
    transform: rotate(0deg);
    opacity: 1;
  }

  65% {
    left: 59%;
    -o-transform: rotate(0deg);
    transform: rotate(0deg);
    opacity: 1;
  }

  100% {
    left: 100%;
    -o-transform: rotate(-180deg);
    transform: rotate(-180deg);
    opacity: 0;
  }
}

/* Adjust modal for the loader */
#customLoadingModal .modal-content {
  background: rgba(0, 0, 0, 0.85);
  backdrop-filter: blur(10px);
  border-radius: 12px;
}

#customLoadingModal .modal-body {
  padding: 60px 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

#customLoadingText {
  font-size: 16px;
  font-weight: 500;
  color: #35C4F0;
  text-shadow: 0 0 10px rgba(53, 196, 240, 0.5);
  margin-top: 20px;
  font-family: 'Segoe UI', Arial, sans-serif;
}

/* Responsive adjustments for loader */
@media (max-width: 768px) {
  .loader-stock {
    width: 400px;
    margin-left: -200px;
  }

  .loader-stock div {
    font-size: 24px;
  }
}

@media (max-width: 576px) {
  .loader-stock {
    width: 300px;
    margin-left: -150px;
  }

  .loader-stock div {
    font-size: 20px;
  }
}
</style>
