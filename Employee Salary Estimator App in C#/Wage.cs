using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Drawing;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows.Forms;

namespace Employee_Wage_Calculator
{
    public partial class Wage : Form
    {

        String getusername = "";
        String getpass = "";
        int num1=0, num2=0, num3=0, num4=0, num5=0, num6=0, num7=0, num8=0, num9=0, num10=0;

        

        public Wage()
        {
            InitializeComponent();
        }

        public Wage(String username , String pass)
        {
            InitializeComponent();

            getusername = username1.Text;
      
         
            label20.Text = "Weekly Wage:";
        }

        private void button1_Click(object sender, EventArgs e)
        {

        }

        private void label2_Click(object sender, EventArgs e)
        {

        }

        private void comboBox1_SelectedIndexChanged(object sender, EventArgs e)
        {
            if (comboBox1.Text == "") {
                warning.Text = "Select grade";
            }
        }

        private void label3_Click(object sender, EventArgs e)
        {

        }

    

        private void label10_Click(object sender, EventArgs e)
        {

        }

        private void textBox4_TextChanged(object sender, EventArgs e)
        {
            if (textBox4.Text == "")
            {
                warning.Text = "Filled the field ";

            }
            else
            {
                num4 = int.Parse(textBox4.Text);

                if (num4 > 60)
                {
                    warning.Text = "Minutes should be at most 60";
                }
                else
                {
                    warning.Text = "";

                }
            }
        }

        private void textBox3_TextChanged(object sender, EventArgs e)
        {
            if (textBox3.Text == "")
            {
                warning.Text = "Filled the field ";

            }
            else
            {
                num3 = int.Parse(textBox3.Text);

                if (num3 > 10)
                {
                    warning.Text = "Hours should be at most 10";
                }
                else
                {
                    warning.Text = "";

                }
            }
        }

        private void label9_Click(object sender, EventArgs e)
        {

        }

        private void label8_Click(object sender, EventArgs e)
        {

        }

        private void label21_Click(object sender, EventArgs e)
        {

        }

        private void textBox12_TextChanged(object sender, EventArgs e)
        {
          

        }

        private void label20_Click(object sender, EventArgs e)
        {

        }

        private void textBox11_TextChanged(object sender, EventArgs e)
        {

        }

        private void label22_Click(object sender, EventArgs e)
        {

        }

        private void label20_Click_1(object sender, EventArgs e)
        {
           
        }



        private void textBox1_TextChanged_1(object sender, EventArgs e)
        {
            
            if (textBox1.Text == "")
            {
                warning.Text = "Filled the field ";

            }
            else {
                num1 = int.Parse(textBox1.Text);

                if (num1 > 10)
            {
                warning.Text = "Hours should be at most 10";
            }
            else
            {
                warning.Text = "";

            }
        }
        }

        private void Wage_Load(object sender, EventArgs e)
        {

        }

        private void id1_TextChanged(object sender, EventArgs e)
        {

        }

        private void textBox2_TextChanged(object sender, EventArgs e)
        {
            if (textBox2.Text == "")
            {
                warning.Text = "Filled the field ";

            }
            else
            {
                num2 = int.Parse(textBox2.Text);

                if (num2 > 60)
                {
                    warning.Text = "Minutes should be at most 60";
                }
                else
                {
                    warning.Text = "";

                }
            }
        }

        private void textBox11_TextChanged_1(object sender, EventArgs e)
        {

        }

        private void textBox10_TextChanged(object sender, EventArgs e)
        {
            if (textBox10.Text == "")
            {
                warning.Text = "Filled the field ";

            }
            else
            {
                num10 = int.Parse(textBox10.Text);

                if (num10 > 60)
                {
                    warning.Text = "Minutes should be at most 60";
                }
                else
                {
                    warning.Text = "";

                }
            }
        }

        private void textBox8_TextChanged(object sender, EventArgs e)
        {
            if (textBox8.Text == "")
            {
                warning.Text = "Filled the field ";

            }
            else
            {
                num8 = int.Parse(textBox8.Text);

                if (num8 > 60)
                {
                    warning.Text = "Minutes should be at most 60";
                }
                else
                {
                    warning.Text = "";

                }
            }
        }

        private void textBox6_TextChanged(object sender, EventArgs e)
        {
            if (textBox6.Text == "")
            {
                warning.Text = "Filled the field ";

            }
            else
            {
                num6 = int.Parse(textBox6.Text);

                if (num6 > 60)
                {
                    warning.Text = "Minutes should be at most 60";
                }
                else
                {
                    warning.Text = "";

                }
            }
        }

        private void textBox5_TextChanged(object sender, EventArgs e)
        {
            if (textBox5.Text == "")
            {
                warning.Text = "Filled the field ";

            }
            else
            {
                num5 = int.Parse(textBox5.Text);

                if (num5 > 10)
                {
                    warning.Text = "Hours should be at most 10";
                }
                else
                {
                    warning.Text = "";

                }
            }
        }

        private void textBox7_TextChanged(object sender, EventArgs e)
        {
            if (textBox7.Text == "")
            {
                warning.Text = "Filled the field ";

            }
            else
            {
                num7 = int.Parse(textBox7.Text);

                if (num7 > 10)
                {
                    warning.Text = "Hours should be at most 10";
                }
                else
                {
                    warning.Text = "";

                }
            }
        }
        

        private void textBox9_TextChanged(object sender, EventArgs e)
        {
            if (textBox9.Text == "")
            {
                warning.Text = "Filled the field ";

            }
            else
            {
                num9 = int.Parse(textBox9.Text);

                if (num9 > 10)
                {
                    warning.Text = "Hours should be at most 10";
                }
                else
                {
                    warning.Text = "";

                }
            }
        }

        private void button1_Click_1(object sender, EventArgs e)
        {
            int total_min = num2 + num4 + num6 + num8 + num10;

            int total_hour = num1 + num3 + num5 + num7 + num9;

            int temp_hour = total_hour + (total_min / 60);

            int temp_min = (total_min % 60);

            int res = (temp_hour * 60) + temp_min;

            int salary = 0;

            if (comboBox1.Text == "G-2000")
            {
                salary = 2000;
            }
            else if (comboBox1.Text == "G-1500")
            {
                salary = 1500;
            }
            else if (comboBox1.Text == "G-1000")
            {
                salary = 1000;
            }
            else if (comboBox1.Text == "G-500")
            {
                salary = 500;
            }


            if (comboBox1.Text != "" )
            {
                warning.Text = "";


                if (res <= 2400)
                {
                    Double ans = temp_hour * salary;

                    Double ans1 = (temp_min * salary) / 60.0;

                    ans = ans + ans1;

                    //textBox11.Text = Convert.ToDouble(ans).ToString();
                    double result = Math.Round(ans, 2);
                    textBox11.Text = result.ToString();

                }
                else
                {
                    int tmp = temp_hour - 40; //5

                    Double ans = tmp * salary * 2.0;

                    Double ans1 = 40 * salary;

                    Double ans2 = (temp_min * salary) / 60.0;

                    ans2 = ans2 * 2.0;


                    ans = ans + ans1 + ans2;

                    double result = Math.Round(ans, 2);
                    textBox11.Text = result.ToString() ;

                }
                
            }
            else {
                warning.Text = "Failed Attempt! Please check all the informations.";
            }
           


        }
    }
}
