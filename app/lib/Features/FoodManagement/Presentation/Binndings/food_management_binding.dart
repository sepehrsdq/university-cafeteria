import 'package:get/get.dart';
import 'package:uni_project/Features/FoodManagement/Presentation/Controllers/food_management_controller.dart';

class FoodManagementBinding extends Bindings {
  @override
  void dependencies() {
    Get.lazyPut<FoodManagementController>(() => FoodManagementController());
  }
}
