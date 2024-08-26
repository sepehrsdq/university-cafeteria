import 'package:get/get.dart';
import 'package:uni_project/Features/MainPage/Presentation/Controllers/main_page_controller.dart';

class MainPageBinding extends Bindings {
  @override
  void dependencies() {
    Get.lazyPut<MainPageController>(() => MainPageController());
  }
}
