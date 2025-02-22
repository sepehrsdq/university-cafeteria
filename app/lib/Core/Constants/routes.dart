import 'package:get/get.dart';
import 'package:uni_project/Features/AddStudent/Presentation/Binndings/add_studnet_binding.dart';
import 'package:uni_project/Features/AddStudent/Presentation/add_student_screen.dart';
import 'package:uni_project/Features/BreakFast/Presentation/Binndings/break_fast_binding.dart';
import 'package:uni_project/Features/BreakFast/Presentation/break_fast_screen.dart';
import 'package:uni_project/Features/Dinner/Presentation/Binndings/dinner_binding.dart';
import 'package:uni_project/Features/Dinner/Presentation/dinner_screen.dart';
import 'package:uni_project/Features/FirstPage/Presentation/Bindings/first_page_binding.dart';
import 'package:uni_project/Features/FirstPage/Presentation/first_page_screen.dart';
import 'package:uni_project/Features/FoodManagement/Presentation/Binndings/food_management_binding.dart';
import 'package:uni_project/Features/FoodManagement/Presentation/food_management_screen.dart';
import 'package:uni_project/Features/Lunch/Presentation/Binndings/lunch_binding.dart';
import 'package:uni_project/Features/Lunch/Presentation/lunch_screen.dart';
import 'package:uni_project/Features/MainPage/Presentation/Bindings/main_page_binding.dart';
import 'package:uni_project/Features/MainPage/Presentation/Bindings/staff_main_page_binding.dart';
import 'package:uni_project/Features/MainPage/Presentation/main_page_screen.dart';
import 'package:uni_project/Features/MainPage/Presentation/staff_main_page.dart';
import 'package:uni_project/Features/Reports/Presentation/Binndings/reports_binding.dart';
import 'package:uni_project/Features/Reports/Presentation/reports_screen.dart';
import 'package:uni_project/Features/SelfCarmandi/Presentation/Binndings/self_carmandi_binding.dart';
import 'package:uni_project/Features/SelfCarmandi/Presentation/self_carmandi_screen.dart';
import 'package:uni_project/Features/StudentSelf/Presentation/Binndings/student_self_binding.dart';
import 'package:uni_project/Features/StudentSelf/Presentation/student_self_screen.dart';
import 'package:uni_project/Features/Tickets/Presentation/Binndings/staff_tickets_binding.dart';
import 'package:uni_project/Features/Tickets/Presentation/Binndings/tickets_binding.dart';
import 'package:uni_project/Features/Tickets/Presentation/staff_tickets_screen.dart';
import 'package:uni_project/Features/Tickets/Presentation/tickets_screen.dart';

class AppRoutes {
  static const String firstpage = '/firstpage';
  static const String mainpage = '/mainpage';
  static const String staffmainpage = '/staffmainpage';
  static const String breakfast = '/breakfast';
  static const String lunch = '/lunch';
  static const String dinner = '/dinner';
  static const String selfcarmandi = '/selfcarmandi';
  static const String foodManagement = '/foodManagement';
  static const String reports = '/reports';
  static const String studentself = '/studentself';
  static const String tickets = '/tickets';
  static const String stafftickets = '/stafftickets';
  static const String addstudnet = '/addstudnet';
  static List<GetPage> get pages => [
        GetPage(
            name: AppRoutes.firstpage,
            page: () => const FirstPageScreen(),
            binding: FirstPageBinding()),
        GetPage(
            name: AppRoutes.mainpage,
            page: () => const MainPageScreen(),
            binding: MainPageBinding()),
        GetPage(
            name: AppRoutes.staffmainpage,
            page: () => const StaffMainPageScreen(),
            binding: StaffMainPageBinding()),
        GetPage(
            name: AppRoutes.breakfast,
            page: () => BreakFastScreen(),
            binding: BreakFastBinding()),
        GetPage(
            name: AppRoutes.lunch,
            page: () => LunchScreen(),
            binding: LunchBinding()),
        GetPage(
            name: AppRoutes.dinner,
            page: () => DinnerScreen(),
            binding: DinnerBinding()),
        GetPage(
            name: AppRoutes.selfcarmandi,
            page: () => SelfCarmandiScreen(),
            binding: SelfCarmandiBinding()),
        GetPage(
            name: AppRoutes.foodManagement,
            page: () => FoodManagementScreen(),
            binding: FoodManagementBinding()),
        GetPage(
            name: AppRoutes.reports,
            page: () => ReportsScreen(),
            binding: ReportsBinding()),
        GetPage(
            name: AppRoutes.studentself,
            page: () => StudentSelfScreen(),
            binding: StudentSelfBinding()),
        GetPage(
            name: AppRoutes.tickets,
            page: () => TicketsScreen(),
            binding: TicketsBinding()),
        GetPage(
            name: AppRoutes.stafftickets,
            page: () => StaffTicketsScreen(),
            binding: StaffTicketsBinding()),
        GetPage(
            name: AppRoutes.addstudnet,
            page: () => AddStudentScreen(),
            binding: AddStudnetBinding()),
      ];
}
