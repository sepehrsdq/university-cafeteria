import 'package:get/get.dart';
import 'package:uni_project/Features/AddStudent/Presentation/Controllers/add_student_controller.dart';

class AddStudnetBinding extends Bindings {
  @override
  void dependencies() {
    Get.lazyPut<AddStudentController>(() => AddStudentController());
  }
}
